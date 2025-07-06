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
     * @return array<string, array<int, array<string, int>>>
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
    #[DataProvider('protectedPagesProvider')]
    public function loginActionWithValidPasswordRedirectsToTargetPage(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        $nonceCookie = $this->extractNonceCookieFromResponse($response);

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
    public function loginActionWithInvalidPasswordRedirectToForm(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        $nonceCookie = $this->extractNonceCookieFromResponse($response);

        $formData = (new FormDataFactory())->fromHtmlMarkupAndXpath($html);
        $postRequest = $formData
            ->with('tx_pagepassword_form.password', 'invalid_password')
            ->toPostRequest($request->withUri(new Uri('/login'))->withCookieParams([$nonceCookie->getName() => $nonceCookie->getValue()]));

        $response = $this->executeFrontendSubRequest($postRequest);
        $location = $response->getHeader('Location')[0];
        self::assertStringContainsString('login', $location);
    }

    /**
     * @param array<string, string|int> $page
     */
    #[Test]
    #[DataProvider('protectedPagesProvider')]
    public function loginActionWithInvalidRequestTokenRedirectToForm(array $page): void
    {
        $request = new InternalRequest($page['slug']);
        $response = $this->executeFrontendSubRequest($request, null, true);
        $html = (string)$response->getBody();
        $nonceCookie = $this->extractNonceCookieFromResponse($response);

        $formData = (new FormDataFactory())->fromHtmlMarkupAndXpath($html);
        $postRequest = $formData
            ->with('tx_pagepassword_form.password', 'valid_password')
            ->with('__RequestToken', 'invalid_token')
            ->toPostRequest($request->withUri(new Uri('/login'))->withCookieParams([$nonceCookie->getName() => $nonceCookie->getValue()]));

        $response = $this->executeFrontendSubRequest($postRequest);
        $location = $response->getHeader('Location')[0];
        self::assertStringContainsString('login', $location);
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

    #[Test]
    public function loginFormDisplayCorrectConfiguration(): void
    {
        // TODO
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/SiteStructure.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/ContentElementPagePasswordForm.csv');
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

    protected function extractNonceCookieFromResponse(ResponseInterface $response): SetCookie
    {
        foreach (explode(',', $response->getHeaderLine('Set-Cookie')) as $strCookie) {
            if (str_contains($strCookie, 'typo3nonce')) {
                return SetCookie::fromString($strCookie);
            }
        }
        return SetCookie::fromString('typo3nonce=');

    }
}
