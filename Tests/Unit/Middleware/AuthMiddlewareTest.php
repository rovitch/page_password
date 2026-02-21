<?php

namespace Rovitch\PagePassword\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rovitch\PagePassword\Middleware\AuthMiddleware;
use Rovitch\PagePassword\Service\AuthService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AuthMiddlewareTest extends UnitTestCase
{
    use ProphecyTrait;

    protected EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = new class () implements EventDispatcherInterface {
            public function dispatch(object $event, ?string $eventName = null): object
            {
                return $event;
            }
        };
    }

    #[Test]
    public function testProtectedPageTriggersRedirect(): void
    {
        $this->resetSingletonInstances = true;

        $authService = $this->createMock(AuthService::class);
        $authService->method('withRequest')->willReturnSelf();
        $authService->method('hasActiveProtection')->willReturn(true);
        $authService->method('isCurrentPageLoginForm')->willReturn(false);
        $authService->method('isAccessGranted')->willReturn(false);
        $authService->method('getLoginPageId')->willReturn(123);

        $middleware = new AuthMiddleware($authService, $this->eventDispatcher);

        $request = $this->prepareTestRequest();
        $mockHandler = $this->prophesize(RequestHandlerInterface::class);
        $mockHandler->handle($request)->shouldNotBeCalled();

        $response = $middleware->process($request, $mockHandler->reveal());

        self::assertEquals(303, $response->getStatusCode());
    }

    #[Test]
    public function testAccessGrantedOnProtectedPageDoNotTriggersRedirect(): void
    {
        $this->resetSingletonInstances = true;

        $authService = $this->createMock(AuthService::class);
        $authService->method('withRequest')->willReturnSelf();
        $authService->method('hasActiveProtection')->willReturn(true);
        $authService->method('isCurrentPageLoginForm')->willReturn(false);
        $authService->method('isAccessGranted')->willReturn(true);
        $authService->method('getLoginPageId')->willReturn(123);

        $middleware = new AuthMiddleware($authService, $this->eventDispatcher);

        $request = $this->prepareTestRequest();
        $mockHandler = $this->prophesize(RequestHandlerInterface::class);
        $mockHandler->handle($request)->shouldBeCalled();

        $middleware->process($request, $mockHandler->reveal());
    }

    #[Test]
    public function testSpecialPageTypesBypassesProtection(): void
    {
        $this->resetSingletonInstances = true;

        $authService = $this->createMock(AuthService::class);
        $authService->method('withRequest')->willReturnSelf();
        $authService->method('hasActiveProtection')->willReturn(true);
        $authService->method('isCurrentPageLoginForm')->willReturn(true);
        $authService->method('isAccessGranted')->willReturn(false);
        $authService->method('getLoginPageId')->willReturn(123);

        $middleware = new AuthMiddleware($authService, $this->eventDispatcher);

        $request = $this->prepareTestRequest();
        $mockHandler = $this->prophesize(RequestHandlerInterface::class);
        $mockHandler->handle($request)->shouldBeCalled();

        $middleware->process($request, $mockHandler->reveal());
    }

    private function prepareTestRequest(string $pageType = '0'): ServerRequestInterface
    {
        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $pageArguments = new PageArguments(1, $pageType, []);

        $siteRouter = $this->createMock(PageRouter::class);
        $site = $this->createMock(Site::class);
        $site->method('getRouter')->willReturn($siteRouter);

        $request = new ServerRequest('https://example.com/page');

        return $request->withAttribute('routing', $pageArguments)
            ->withAttribute('frontend.user', $frontendUser)
            ->withAttribute('site', $site);
    }
}
