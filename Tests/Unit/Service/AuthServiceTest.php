<?php

namespace Rovitch\PagePassword\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Rovitch\PagePassword\Service\AuthService;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AuthServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $linkService = $this->createMock(\TYPO3\CMS\Core\LinkHandling\LinkService::class);
        $linkService->method('resolve')->willReturn(['pageuid' => 123]);
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\LinkHandling\LinkService::class, $linkService);
    }

    /**
     * @return array<string, array<array<int, array<string|int>>>>
     */
    public static function pageAuthenticationStatusProvider(): array
    {
        return [
            'authenticated user accessing protected page' => [
                [
                    [
                        'uid' => 1,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_password_changed_at' => time(),
                    ],
                ],
                true,
            ],
            'unauthenticated user accessing protected page' => [
                [
                    [
                        'uid' => 2,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_password_changed_at' => time(),
                    ],
                ],
                false,
            ],
            'authenticated user accessing page with parent protection' => [
                [
                    [
                        'uid' => 4,
                        'tx_pagepassword_enable' => 0,
                        'tx_pagepassword_extend_to_subpages' => 0,
                    ],
                    [
                        'uid' => 3,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_extend_to_subpages' => 1,
                        'tx_pagepassword_password_changed_at' => time(),
                    ],
                ],
                true,
            ],
            'unauthenticated user accessing translated protected page' => [
                [
                    [
                        'uid' => 5,
                        '_LOCALIZED_UID' => 6,
                        'tx_pagepassword_enable' => 1,
                    ],
                ],
                false,
            ],
            'authenticated user accessing translated protected page' => [
                [
                    [
                        'uid' => 7,
                        '_LOCALIZED_UID' => 8,
                        'tx_pagepassword_enable' => 1,
                    ],
                ],
                true,
            ],
            'unauthenticated user accessing protected page with translated parent' => [
                [
                    [
                        'uid' => 11,
                        '_LOCALIZED_UID' => 12,
                        'tx_pagepassword_enable' => 0,
                        'tx_pagepassword_extend_to_subpages' => 0,
                    ],
                    [
                        'uid' => 9,
                        '_LOCALIZED_UID' => 10,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_extend_to_subpages' => 1,
                        'tx_pagepassword_password_changed_at' => time(),
                    ],
                ],
                false,
            ],
            'authenticated user accessing translated page with protection' => [
                [
                    [
                        'uid' => 13,
                        '_LOCALIZED_UID' => 14,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_extend_to_subpages' => 0,
                        'tx_pagepassword_password_changed_at' => time(),
                    ],
                ],
                true,
            ],
        ];
    }

    /**
     * @param array<int, array<int|string>> $pageHierarchy
     * @param bool $expectedAuthStatus
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    #[Test]
    #[DataProvider('pageAuthenticationStatusProvider')]
    public function testUserAuthenticationStatus(array $pageHierarchy, bool $expectedAuthStatus): void
    {
        $this->resetSingletonInstances = true;

        $request = $this->createTestRequest();
        $frontendUser = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUser->getSessionData('tx_pagepassword_unlocked_pages')
            ->shouldBeCalled()
            ->willReturn(
                [
                    [
                        'uid' => 1,
                        'timestamp' => time() + 60,
                    ],
                    [
                        'uid' => 3,
                        'timestamp' => time() + 60,
                    ],
                    [
                        'uid' => 8,
                        'timestamp' => time() + 60,
                    ],
                    [
                        'uid' => 9,
                        'timestamp' => time() + 60,
                    ],
                    [
                        'uid' => 14,
                        'timestamp' => time() + 60,
                    ],
                ],
            );

        $request = $request->withAttribute('frontend.user', $frontendUser->reveal());

        $rootlineUtility = $this->createMock(RootlineUtility::class);
        $rootlineUtility->method('get')->willReturn($pageHierarchy);

        $authService = new AuthService();
        $authService->setRootlineUtility($rootlineUtility);
        $authService->withRequest($request);
        $authStatus = $authService->isAccessGranted();

        self::assertEquals($expectedAuthStatus, $authStatus);
    }

    /**
     * @return array<string, array<array<int, array<string|int>>>>
     * @throws InvalidPasswordHashException
     */
    public static function pageUnlockScenarioProvider(): array
    {
        $passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $hashInstance = $passwordHashFactory->getDefaultHashInstance('BE');
        $hashedPassword = $hashInstance->getHashedPassword('test');

        return [
            'unlocking directly protected page' => [
                [
                    [
                        'uid' => 1,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_extend_to_subpages' => 0,
                        'tx_pagepassword_password' => $hashedPassword,
                    ],
                ],
            ],
            'unlocking page with inherited protection' => [
                [
                    [
                        'uid' => 2,
                        'tx_pagepassword_enable' => 0,
                        'tx_pagepassword_extend_to_subpages' => 0,
                    ],
                    [
                        'uid' => 1,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_extend_to_subpages' => 1,
                        'tx_pagepassword_password' => $hashedPassword,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<int|string>> $pageHierarchy
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    #[Test]
    #[DataProvider('pageUnlockScenarioProvider')]
    public function testUnlockPageWithValidPassword(array $pageHierarchy): void
    {
        $this->resetSingletonInstances = true;

        $request = $this->createTestRequest();
        $controller = $request->getAttribute('frontend.controller');
        $controller->rootLine = $pageHierarchy;

        $frontendUser = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUser->getSessionData('tx_pagepassword_unlocked_pages')->shouldBeCalled();
        //$frontendUser->setAndSaveSessionData('tx_pagepassword_unlocked_pages', [['timestamp' => Argument::type('integer'), 'uid' => 1]])->shouldBeCalled();
        $frontendUser->setAndSaveSessionData('tx_pagepassword_unlocked_pages', Argument::type('array'))->shouldBeCalled();

        $request = $request->withAttribute('frontend.user', $frontendUser->reveal());

        $rootlineUtility = $this->createMock(RootlineUtility::class);
        $rootlineUtility->method('get')->willReturn($pageHierarchy);

        $authService = new AuthService();
        $authService->setRootlineUtility($rootlineUtility);
        $authService->withRequest($request);
        $unlockSuccessful = $authService->attemptPageUnlock('test');

        self::assertTrue($unlockSuccessful, 'Page should be unlocked with valid password');
    }

    #[Test]
    public function testUnlockFailsWithInvalidHash(): void
    {
        $this->resetSingletonInstances = true;

        $pageWithInvalidHash = [
            [
                'uid' => 1,
                'tx_pagepassword_enable' => 1,
                'tx_pagepassword_extend_to_subpages' => 0,
                'tx_pagepassword_password' => 'invalidHash',
            ],
        ];

        $request = $this->createTestRequest();

        $rootlineUtility = $this->createMock(RootlineUtility::class);
        $rootlineUtility->method('get')->willReturn($pageWithInvalidHash);

        $authService = new AuthService();
        $authService->setRootlineUtility($rootlineUtility);
        $authService->withRequest($request);
        $unlockResult = $authService->attemptPageUnlock('test');

        self::assertFalse($unlockResult, 'Page unlock should fail with invalid password hash');
    }

    #[Test]
    public function testIdentifySpecialPageTypes(): void
    {
        $this->resetSingletonInstances = true;

        $request = $this->createTestRequest('0', 123);

        $rootlineUtility = $this->createMock(RootlineUtility::class);
        $rootlineUtility->method('get')->willReturn([]);

        $authService = new AuthService();
        $authService->setRootlineUtility($rootlineUtility);
        $authService->withRequest($request);
        $isSpecialPage = $authService->isCurrentPageLoginForm();

        self::assertTrue($isSpecialPage, 'Should identify login/form page correctly');
    }

    #[Test]
    public function testLoginPageIdReturnCorrectValue(): void
    {
        $this->resetSingletonInstances = true;

        $request = $this->createTestRequest();

        $rootlineUtility = $this->createMock(RootlineUtility::class);
        $rootlineUtility->method('get')->willReturn([]);

        $authService = new AuthService();
        $authService->setRootlineUtility($rootlineUtility);
        $authService->withRequest($request);
        $loginPageId = $authService->getLoginPageId();

        self::assertEquals(123, $loginPageId, 'Should identify login page id correctly');
    }

    #[Test]
    public function testActiveProtection(): void
    {
        $this->resetSingletonInstances = true;

        $request = $this->createTestRequest();

        $rootlineUtility = $this->createMock(RootlineUtility::class);
        $rootlineUtility->method('get')->willReturn([
            [
                'uid' => 1,
                'tx_pagepassword_enable' => 1,
            ],
        ]);

        $authService = new AuthService();
        $authService->setRootlineUtility($rootlineUtility);
        $authService->withRequest($request);
        $hasActiveProtection = $authService->hasActiveProtection();

        self::assertTrue($hasActiveProtection, 'Protection should be active on page');
    }

    private function createTestRequest(string $pageType = '0', int $pageId = 1): ServerRequestInterface
    {
        $frontendController = $this->createMock(TypoScriptFrontendController::class);
        $frontendController->rootLine = [];

        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $pageArguments = new PageArguments($pageId, $pageType, []);

        $request = new ServerRequest('https://example.com/');
        $site = $this->createMock(\TYPO3\CMS\Core\Site\Entity\Site::class);
        $siteRouter = $this->createMock(\TYPO3\CMS\Core\Routing\PageRouter::class);
        $site->method('getRouter')->willReturn($siteRouter);
        $site->method('getConfiguration')->willReturn(['pagepassword_default_login_page' => 't3://page?uid=123']);

        return $request->withAttribute('routing', $pageArguments)
            ->withAttribute('frontend.controller', $frontendController)
            ->withAttribute('frontend.user', $frontendUser)
            ->withAttribute('site', $site);
    }
}
