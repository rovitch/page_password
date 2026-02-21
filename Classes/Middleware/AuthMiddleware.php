<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Middleware;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rovitch\PagePassword\Event\BeforeAccessIsGrantedEvent;
use Rovitch\PagePassword\Service\AuthService;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\Entity\Site;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authService = $this->authService->withRequest($request);

        $accessGranted = !$authService->hasActiveProtection() || $authService->isCurrentPageLoginForm() || $authService->isAccessGranted();

        /** @var BeforeAccessIsGrantedEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new BeforeAccessIsGrantedEvent($accessGranted, $request, $authService),
        );

        $accessGranted = $event->isAccessGranted();
        if ($accessGranted) {
            return $handler->handle($request);
        }

        $requestUri = $request->getUri();
        $redirectUri = (new Uri())
            ->withPath($requestUri->getPath())
            ->withQuery($requestUri->getQuery());

        $queryParams = [
            '_language' => $request->getAttribute('language'),
            'tx_pagepassword_form' => [
                'uid' => $request->getAttribute('routing')->getPageId(),
                'redirect_uri' => $redirectUri->__toString(),
            ],
        ];

        /** @var Site $site */
        $site = $request->getAttribute('site');
        $uri = $site->getRouter()
            ->generateUri($authService->getLoginPageId(), $queryParams, '', RouterInterface::ABSOLUTE_URL);

        return new RedirectResponse($uri, 303);
    }
}
