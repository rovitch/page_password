<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Rovitch\PagePassword\Service\AuthService;
use Rovitch\PagePassword\Utility\RequestUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class AuthController extends ActionController
{
    protected LanguageService $languageService;

    protected string $redirectUri;

    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly Context $context,
        private readonly LoggerInterface $logger,
        private AuthService $authService,
    ) {}

    protected function initializeAction(): void
    {
        $this->logger->debug('Init PagePassword action');
        $language = $this->request->getAttribute('language');
        $this->languageService = $this->languageServiceFactory->createFromSiteLanguage($language);
        $this->authService = $this->authService->withRequest($this->request);
        $this->redirectUri = $this->getRedirectUri()->__toString();
    }

    public function formAction(): ResponseInterface
    {
        $this->logger->debug('Check if access is already granted and redirect to target page');
        if (!$this->authService->hasActiveProtection() || $this->authService->isAccessGranted()) {
            $this->logger->debug('Access already granted, redirect to target uri {uri}', ['uri' => $this->redirectUri]);
            $response = new RedirectResponse($this->redirectUri);
            throw new PropagateResponseException($response, 303);
        }
        $this->logger->debug('Access not granted, render login form');
        return $this->renderForm();
    }

    /**
     * @throws PropagateResponseException
     */
    public function loginAction(): ResponseInterface
    {
        $securityAspect = SecurityAspect::provideIn($this->context);
        $requestToken = $securityAspect->getReceivedRequestToken();

        if ($requestToken === null || $requestToken === false || $requestToken->scope !== 'auth/login') {
            $this->logger->debug('Missing or invalid request token during login', ['requestToken' => $requestToken]);
            return $this->renderFormWithError(
                $this->languageService->sL('LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:form.invalid_token'),
            );
        }

        if ($requestToken->getSigningSecretIdentifier() !== null) {
            $securityAspect->getSigningSecretResolver()->revokeIdentifier(
                $requestToken->getSigningSecretIdentifier(),
            );
        }

        $parameters = RequestUtility::extractParameters($this->request);
        if ($this->authService->attemptPageUnlock($parameters['password'] ?? '')) {
            $this->logger->info('Successful PagePassword unlock on page uid "{uid}" from "{ip}"', [
                'uid' => RequestUtility::extractProtectedPageId($this->request),
                'ip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            ]);
            $response =  new RedirectResponse($this->redirectUri);
            throw new PropagateResponseException($response, 303);
        }

        return $this->renderFormWithError(
            $this->languageService->sL('LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:form.invalid_password'),
        );
    }

    protected function renderFormWithError(string $message): ResponseInterface
    {
        $this->logger->warning('PagePassword unlock failed on page uid "{uid}" from "{ip}" with message "{message}"', [
            'uid' => RequestUtility::extractProtectedPageId($this->request),
            'ip' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'message' => $message,
        ]);

        $this->addFlashMessage(
            $message,
            $this->languageService->sL('LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:form.error'),
            ContextualFeedbackSeverity::ERROR,
        );

        return $this->renderForm();
    }

    protected function renderForm(): ResponseInterface
    {
        $requestToken = RequestToken::create('auth/login');
        $protectedPageId = RequestUtility::extractProtectedPageId($this->request);

        // @extensionScannerIgnoreLine
        $record = $this->request->getAttribute('currentContentObject')->data;

        $this->view->assignMultiple([
            'record' => $record,
            'redirectUri' => $this->redirectUri,
            'protectedPageId' => $protectedPageId,
            'settings' => $this->settings,
            'requestToken' => $requestToken,
        ]);
        /** @phpstan-ignore method.notFound */
        $this->view->getRenderingContext()->setControllerAction('form');

        return $this->htmlResponse();
    }

    /**
     * Retrieves the redirect URI based on the request parameters. If the provided
     * redirect URI is invalid or does not match the host of the server URI, a fallback
     * URI is returned.
     *
     * @todo see https://github.com/TYPO3/typo3/blob/main/typo3/sysext/felogin/Classes/Validation/RedirectUrlValidator.php
     * for better url validation
     *
     * @return UriInterface The valid redirect URI or a fallback URI.
     */
    protected function getRedirectUri(): UriInterface
    {
        $parameters = RequestUtility::extractParameters($this->request);
        $redirectUri = $parameters['redirect_uri'] ?? '';
        return $this->isRelativeUrl($redirectUri) ? new Uri($redirectUri) : new Uri('/');
    }

    protected function isRelativeUrl(string $url): bool
    {
        $url = GeneralUtility::sanitizeLocalUrl($url);
        if (!empty($url)) {
            $parsedUrl = @parse_url($url);
            if ($parsedUrl !== false && !isset($parsedUrl['scheme']) && !isset($parsedUrl['host'])) {
                // If the relative URL starts with a slash, we need to check if it's within the current site path
                return $parsedUrl['path'][0] !== '/' || str_starts_with($parsedUrl['path'], GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
            }
        }
        return false;
    }
}
