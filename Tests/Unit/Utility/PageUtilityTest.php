<?php

namespace Rovitch\PagePassword\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Rovitch\PagePassword\Utility\PageUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageUtilityTest extends UnitTestCase
{
    /**
     * @return array<string, array<string|int>>
     */
    public static function pageIdExtractionDataProvider(): array
    {
        return [
            'standard page' => [
                [
                    'uid' => 1,
                ],
                1,
            ],
            'translated page with localized uid' => [
                [
                    'uid' => 1,
                    '_LOCALIZED_UID' => 2,
                ],
                2,
            ],
            'translated page with localized uid (TYPO3 < 13)' => [
                [
                    'uid' => 1,
                    '_PAGES_OVERLAY_UID' => 2,
                ],
                2,
            ],
        ];
    }

    /**
     * @param array<string|int> $pageRecord
     * @param int $expectedPageId
     */
    #[Test]
    #[DataProvider('pageIdExtractionDataProvider')]
    public function testPageIdExtraction(array $pageRecord, int $expectedPageId): void
    {
        self::assertEquals($expectedPageId, PageUtility::extractPageId($pageRecord));
    }

    /**
     * @return array<string, array<array<int, array<string|int>>>>
     */
    public static function pageProtectionStatusDataProvider(): array
    {
        return [
            'page with direct protection is correctly detected' => [
                [
                    0 => [
                        'uid' => 2,
                        'tx_pagepassword_enable' => 1,
                    ],
                    1 => [
                        'uid' => 1,
                        'tx_pagepassword_enable' => 0,
                    ],
                ],
                true,
            ],
            'empty page hierarchy has no protection' => [
                [],
                false,
            ],
            'subpage inherits protection when parent enables inheritance' => [
                [
                    0 => [
                        'uid' => 2,
                        'tx_pagepassword_enable' => 0,
                        'tx_pagepassword_extend_to_subpages' => 0,
                    ],
                    1 => [
                        'uid' => 1,
                        'tx_pagepassword_enable' => 1,
                        'tx_pagepassword_extend_to_subpages' => 1,
                    ],
                ],
                true,
            ],
            'subpage has no protection when parent disables inheritance' => [
                [
                    [
                        0 => [
                            'uid' => 2,
                            'tx_pagepassword_enable' => 0,
                            'tx_pagepassword_extend_to_subpages' => 0,
                        ],
                        1 => [
                            'uid' => 1,
                            'tx_pagepassword_enable' => 1,
                            'tx_pagepassword_extend_to_subpages' => 0,
                        ],
                    ],
                ],
                false,
            ],
        ];
    }

    /**
     * @param array<int, array<int|string>> $pageHierarchy
     * @param bool $expectedProtectionStatus
     */
    #[Test]
    #[DataProvider('pageProtectionStatusDataProvider')]
    public function testPageProtectionStatus(array $pageHierarchy, bool $expectedProtectionStatus): void
    {
        self::assertEquals($expectedProtectionStatus, PageUtility::hasActiveProtection($pageHierarchy));
    }
}
