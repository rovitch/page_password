<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Service;

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Rovitch\PagePassword\Utility\PageUtility;
use Rovitch\PagePassword\Utility\RequestUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class AuthService
{
    protected const SESSION_KEY_UNLOCKED_PAGES = 'tx_pagepassword_unlocked_pages';

    protected ?RootlineUtility $rootlineUtility = null;

    protected int $loginPageId;

    protected int $currentPageId;

    /**
     * @var array<int, array<int|string>>
     */
    protected array $rootLine = [];

    /**
     * @var array<array<string|int>>
     */
    protected array $unlockedPages = [];

    protected FrontendUserAuthentication $user;
    public function __construct(
        private readonly LinkService $linkService,
        private readonly LoggerInterface $logger,
        private readonly SiteFinder $siteFinder,
    ) {}

    public function setRootlineUtility(?RootlineUtility $rootlineUtility): void
    {
        $this->rootlineUtility = $rootlineUtility;
    }

    protected function getRootlineUtility(int $pageId, ?int $languageId = null): RootlineUtility
    {
        if ($this->rootlineUtility === null) {
            $context = $languageId === null ? null : $this->createContextForLanguage($pageId, $languageId);
            return GeneralUtility::makeInstance(RootlineUtility::class, $pageId, '', $context);
        }
        return $this->rootlineUtility;
    }

    /**
     * Protection fields may differ per language (allowLanguageSynchronization), so the rootline of the
     * protected page has to be resolved in the language it was requested in, not in the login page's language.
     */
    protected function createContextForLanguage(int $pageId, int $languageId): ?Context
    {
        try {
            $siteLanguage = $this->siteFinder->getSiteByPageId($pageId)->getLanguageById($languageId);
        } catch (SiteNotFoundException|\InvalidArgumentException) {
            $this->logger->debug('Language {language} not available for page {page}', ['language' => $languageId, 'page' => $pageId]);
            return null;
        }
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));
        return $context;
    }

    /**
     * Configures the service with data from the request
     *
     * @param ServerRequestInterface $request The HTTP request containing the necessary attributes
     * @return self For method chaining
     * @throws \InvalidArgumentException|Exception If required, request attributes are missing
     */
    public function withRequest(ServerRequestInterface $request): self
    {
        $this->logger->debug('Init PagePassword AuthService');
        $this->initLoginPageId($request);

        $this->currentPageId = $request->getAttribute('routing')->getPageId();
        $this->user = $request->getAttribute('frontend.user');

        $unlockedPages = $this->user->getSessionData(self::SESSION_KEY_UNLOCKED_PAGES);
        $this->unlockedPages = is_array($unlockedPages) ? $unlockedPages : [];

        $protectedPageId = RequestUtility::extractProtectedPageId($request);
        $rootlinePageId = $protectedPageId === 0 ? $this->currentPageId : $protectedPageId;
        $rootlineLanguageId = $protectedPageId === 0 ? null : RequestUtility::extractProtectedPageLanguageId($request);
        $rootlineUtility = $this->getRootlineUtility($rootlinePageId, $rootlineLanguageId);

        try {
            $rootline = $rootlineUtility->get();
        } catch (\RuntimeException) {
            $this->logger->debug('Failed to retrieve rootline');
            $rootline = [];
        }

        $this->rootLine = $rootline;

        return $this;
    }

    protected function initLoginPageId(ServerRequestInterface $request): void
    {
        $siteConfiguration = $request->getAttribute('site')->getConfiguration();
        $loginPageLink = $siteConfiguration['pagepassword_default_login_page'] ?? '';
        $linkData = $this->linkService->resolve($loginPageLink);
        $this->loginPageId = (int)($linkData['pageuid'] ?? 0);
    }

    public function getLoginPageId(): int
    {
        if ($this->loginPageId === 0) {
            throw new \InvalidArgumentException('Login page configuration is not set', 5196046285);
        }
        return $this->loginPageId;
    }

    public function hasActiveProtection(): bool
    {
        return PageUtility::hasActiveProtection($this->rootLine);
    }

    public function isAccessGranted(): bool
    {
        $protectedPage = PageUtility::findProtectedPageInRootLine($this->rootLine);

        if (empty($protectedPage)) {
            return false;
        }

        $protectedPageId = PageUtility::extractPageId($protectedPage);
        $passwordChangedAt = PageUtility::extractPagePasswordChangedAt($protectedPage);

        $matchingUnlockedPages = array_filter(
            $this->unlockedPages,
            fn($unlockedPage) => isset($unlockedPage['uid']) && $unlockedPage['uid'] === $protectedPageId,
        );

        if (empty($matchingUnlockedPages)) {
            return false;
        }

        $candidate = reset($matchingUnlockedPages);

        $accessGranted = isset($candidate['timestamp']) && $candidate['timestamp'] > $passwordChangedAt;

        if (!$accessGranted) {
            $this->logger->debug('Timestamp outdated, remove page access from user session data');
            $this->removePageAccess($protectedPageId);
        }

        return $accessGranted;
    }

    public function isCurrentPageLoginForm(): bool
    {
        return $this->currentPageId === $this->loginPageId;
    }

    public function attemptPageUnlock(string $password): bool
    {
        $protectedPage = PageUtility::findProtectedPageInRootLine($this->rootLine);

        $isValid = $this->validatePasswordHash($password, PageUtility::extractPagePassword($protectedPage));

        if ($isValid) {
            $this->grantPageAccess(PageUtility::extractPageId($protectedPage));
        }

        return $isValid;
    }

    protected function validatePasswordHash(string $password, string $hash): bool
    {
        try {
            $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->get($hash, 'BE');
            return $hashInstance->checkPassword($password, $hash);
        } catch (InvalidPasswordHashException) {
            $this->logger->debug('Password hash is not valid');
            return false;
        }
    }

    protected function grantPageAccess(int $pageUid): void
    {
        $this->removeDuplicatePageAccess($pageUid);
        $this->unlockedPages[] = $this->createPageAccessEntry($pageUid);
        $this->setAndSaveSessionData();
    }

    protected function removePageAccess(int $pageUid): void
    {
        $this->removeDuplicatePageAccess($pageUid);
        $this->unlockedPages = array_filter(
            $this->unlockedPages,
            fn($unlockedPage) => $unlockedPage['uid'] !== $pageUid
        );
        $this->setAndSaveSessionData();
    }

    protected function setAndSaveSessionData(): void
    {
        $this->logger->debug('Set and save session data', array_values($this->unlockedPages));
        $this->user->setAndSaveSessionData(self::SESSION_KEY_UNLOCKED_PAGES, array_values($this->unlockedPages));
    }

    protected function removeDuplicatePageAccess(int $pageUid): void
    {
        foreach ($this->unlockedPages as $index => $unlockedPage) {
            if ($unlockedPage['uid'] === $pageUid) {
                unset($this->unlockedPages[$index]);
            }
        }
    }

    /**
     * @param int $pageUid
     * @return array<string|int>
     */
    protected function createPageAccessEntry(int $pageUid): array
    {
        return [
            'timestamp' => time(),
            'uid' => $pageUid,
        ];
    }
}
