<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Rovitch\PagePassword\Service\AuthService;
use Rovitch\PagePassword\Utility\RequestUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class AuthController extends ActionController
{
    protected AuthService $authService;

    protected LanguageService $languageService;

    protected TypoScriptFrontendController $frontendController;

    protected string $redirectUri;

    protected function initializeAction(): void
    {
        $this->frontendController = $this->request->getAttribute('frontend.controller');
        $language = $this->request->getAttribute('language');
        $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($language);
        $this->authService = (new AuthService())->withRequest($this->request);
        $this->redirectUri = $this->getRedirectUri()->__toString();
    }

    public function formAction(): ResponseInterface
    {
        if (!$this->authService->hasActiveProtection() || $this->authService->isAccessGranted()) {
            $response = $this->responseFactory->createResponse()->withHeader('Location', $this->redirectUri);
            throw new PropagateResponseException($response, 303);
        }

        $requestToken = RequestToken::create('auth/login');
        $protectedPageId = RequestUtility::extractProtectedPageId($this->request);
        $record = $this->request->getAttribute('currentContentObject')->data;
        $this->view->assignMultiple([
            'record' => $record,
            'redirectUri' => $this->redirectUri,
            'protectedPageId' => $protectedPageId,
            'settings' => $this->settings,
            'requestToken' => $requestToken,
        ]);

        return $this->htmlResponse();
    }

    /**
     * @throws PropagateResponseException
     */
    public function loginAction(): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $securityAspect = SecurityAspect::provideIn($context);
        $requestToken = $securityAspect->getReceivedRequestToken();

        if ($requestToken === null || $requestToken === false || $requestToken->scope !== 'auth/login') {
            return $this->redirectToLoginFormWithErrorMessage(
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
            $response = $this->responseFactory->createResponse()->withHeader('Location', $this->redirectUri);
            throw new PropagateResponseException($response, 303);
        }

        return $this->redirectToLoginFormWithErrorMessage(
            $this->languageService->sL('LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:form.invalid_password'),
        );
    }

    /**
     * @throws PropagateResponseException
     */
    protected function redirectToLoginFormWithErrorMessage(string $message): ResponseInterface
    {
        $this->addFlashMessage(
            $message,
            $this->languageService->sL('LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:form.error'),
            ContextualFeedbackSeverity::ERROR,
        );

        $parameters = RequestUtility::extractParameters($this->request);
        $uri = $this->uriBuilder->reset()->setTargetPageUid($this->frontendController->id)->setArguments([
            'tx_pagepassword_form' => [
                'uid' => $parameters['uid'] ?? 0,
                'redirect_uri' => $this->redirectUri,
            ],
        ])->build();

        $response = $this->responseFactory->createResponse()->withHeader('Location', $uri);
        throw new PropagateResponseException($response, 303);
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

        $serverUri = $this->request->getUri();
        $fallBackUri = (new Uri())->withScheme($serverUri->getScheme())->withHost($serverUri->getHost());

        try {
            $parsedRedirectUri = new Uri($redirectUri);
            if ($parsedRedirectUri->getHost() !== $serverUri->getHost()) {
                return $fallBackUri;
            }
            return $parsedRedirectUri;
        } catch (\InvalidArgumentException) {
            return $fallBackUri;
        }
    }
}
