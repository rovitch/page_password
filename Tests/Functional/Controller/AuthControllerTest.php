<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Tests\Functional\Controller;

use GuzzleHttp\Cookie\SetCookie;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Rovitch\PagePassword\Controller\AuthController;
use Rovitch\PagePassword\Tests\Functional\Framework\FormHandling\FormDataFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(AuthController::class)]
final class AuthControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-fluid',
        'typo3/cms-extbase',
        'typo3/cms-frontend',
        'typo3/cms-fluid-styled-content',
    ];

    protected array $testExtensionsToLoad = ['rovitch/page-password'];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/page_password/Tests/Functional/Fixtures/Sites/' => 'typo3conf/sites',
    ];

    /**
     * @return array<string, list<array<string, int|string>>>
     */
    public static function protectedPagesProvider(): array
    {
        return [
            'page has protection enabled' => [
                [
                    'uid' => 2,
                    'slug' => '/protected-page',
                ],
            ],
            'page inherit protection' => [
                [
                    'uid' => 3,
                    'slug' => '/subpage-inherit-protection',
                ],
            ],
            '[fr] page has protection enabled' => [
                [
                    'uid' => 2,
                    'slug' => '/fr/page-protegee',
                ],
            ],
            '[fr] page inherit protection' => [
                [
                    'uid' => 3,
                    'slug' => '/fr/sous-page-protege-herite-de-la-protection',
                ],
            ],
        ];
    }

    /**
     * @return array<string, list<array<string, int|string>>>
     */
    public static function translatedProtectedPagesProvider(): array
    {
        return [
            '[fr] page has protection enabled' => [
                [
                    'uid' => 2,
                    'slug' => '/fr/page-protegee',
                    'orig_slug' => '/protected-page',
                ],
            ],
            '[fr] page inherit protection' => [
                [
                    'uid' => 3,
                    'slug' => '/fr/sous-page-protege-herite-de-la-protection',
                    'orig_slug' => '/subpage-inherit-protection',
                ],
            ],
        ];
    }

    #[Test]
    public function pageIsNotProtected(): void
    {
        $request = new InternalRequest('/');
        $html = (string)$this->executeFrontendSubRequest($request)->getBody();
        self::assertStringContainsString('Home page not protected', $html);
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('protectedPagesProvider')]
    public function protectedPageRedirectToLoginForm(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request);

        self::assertInstanceOf(RedirectResponse::class, $response);

        $location = $response->getHeader('Location')[0];
        $expectedQueryParameters = [
            [
                'tx_pagepassword_form' => [
                    'uid' => $page['uid'],
                ],
            ],
            [
                'tx_pagepassword_form' => [
                    'redirect_uri' => $page['slug'],
                ],
            ],
        ];

        foreach ($expectedQueryParameters as $params) {
            self::assertStringContainsString(http_build_query($params), $location);
        }
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('translatedProtectedPagesProvider')]
    public function translatedProtectedPageWithUnlockedParentRedirectToLoginForm(array $page): void
    {
        // Login to the main language page
        $request = new InternalRequest($page['orig_slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        $nonceCookie = $this->extractCookieFromResponse($response, 'typo3nonce');

        $formData = (new FormDataFactory())->fromHtmlMarkupAndXpath($html);
        $postRequest = $formData
            ->with('tx_pagepassword_form.password', 'valid_password')
            ->toPostRequest($request->withUri(new Uri('/login'))->withCookieParams([$nonceCookie->getName() => $nonceCookie->getValue()]));

        $response = $this->executeFrontendSubRequest($postRequest);
        $feTypoUserCookie = $this->extractCookieFromResponse($response, 'fe_typo_user');

        // Fetch translated page with cookie from the main language unlocked page
        $request = (new InternalRequest($page['slug']))->withCookieParams([$feTypoUserCookie->getName() => $feTypoUserCookie->getValue()]);
        $response = $this->executeFrontendSubRequest($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('protectedPagesProvider')]
    public function loginActionWithValidPasswordRedirectsToTargetPage(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        $nonceCookie = $this->extractCookieFromResponse($response, 'typo3nonce');

        $formData = (new FormDataFactory())->fromHtmlMarkupAndXpath($html);
        $postRequest = $formData
            ->with('tx_pagepassword_form.password', 'valid_password')
            ->toPostRequest($request->withUri(new Uri('/login'))->withCookieParams([$nonceCookie->getName() => $nonceCookie->getValue()]));

        $response = $this->executeFrontendSubRequest($postRequest);
        $location = $response->getHeader('Location')[0];
        self::assertStringContainsString($page['slug'], $location);
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('protectedPagesProvider')]
    public function loginActionWithInvalidPasswordShowFormWithError(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        $nonceCookie = $this->extractCookieFromResponse($response, 'typo3nonce');

        $formData = (new FormDataFactory())->fromHtmlMarkupAndXpath($html);
        $postRequest = $formData
            ->with('tx_pagepassword_form.password', 'invalid_password')
            ->toPostRequest($request->withUri(new Uri('/login'))->withCookieParams([$nonceCookie->getName() => $nonceCookie->getValue()]));

        $responseHtml = (string)$this->executeFrontendSubRequest($postRequest)->getBody();
        self::assertStringContainsString('Invalid password', $responseHtml);
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('protectedPagesProvider')]
    public function loginActionWithInvalidRequestTokenShowFormWithError(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        $nonceCookie = $this->extractCookieFromResponse($response, 'typo3nonce');

        $formData = (new FormDataFactory())->fromHtmlMarkupAndXpath($html);
        $postRequest = $formData
            ->with('tx_pagepassword_form.password', 'valid_password')
            ->with('__RequestToken', 'invalid_token')
            ->toPostRequest($request->withUri(new Uri('/login'))->withCookieParams([$nonceCookie->getName() => $nonceCookie->getValue()]));

        $responseHtml = (string)$this->executeFrontendSubRequest($postRequest)->getBody();
        self::assertStringContainsString('Invalid request token', $responseHtml);
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('protectedPagesProvider')]
    public function protectedPageRedirectToLoginFormShowForm(array $page): void
    {
        $request = (new InternalRequest())->withPageId($page['uid']);
        $html = (string)$this->executeFrontendSubRequest($request, null, true)->getBody();
        self::assertStringContainsString(
            'name="tx_pagepassword_form[__referrer][@extension]" value="PagePassword"',
            $html,
        );
        self::assertStringContainsString('name="tx_pagepassword_form[__referrer][@controller]" value="Auth"', $html);
        self::assertStringContainsString('name="tx_pagepassword_form[__referrer][@action]" value="form"', $html);
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('protectedPagesProvider')]
    public function loginFormDisplayCorrectConfiguration(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        self::assertStringContainsString('pagePassword.addCustomStyles(`#db5151`, `#e79b32`);', $html);
        self::assertStringContainsString('dark:bg-gray-900', $html);
        self::assertStringContainsString('/user_upload/your_logo_light.png', $html);
        self::assertStringContainsString('/user_upload/your_logo_dark.png', $html);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/sys_file_reference.csv');
        $this->setUpFrontendRootPage(1, [
            'constants' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
            ],
            'setup' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                'EXT:page_password/Tests/Functional/Fixtures/TypoScript/Setup/root.typoscript',
                'EXT:page_password/Configuration/TypoScript/setup.typoscript',
            ],
        ]);
    }

    protected function extractCookieFromResponse(ResponseInterface $response, string $name): SetCookie
    {
        foreach (explode(',', $response->getHeaderLine('Set-Cookie')) as $strCookie) {
            if (str_contains($strCookie, $name)) {
                return SetCookie::fromString($strCookie);
            }
        }
        return SetCookie::fromString("$name=");
    }
}
