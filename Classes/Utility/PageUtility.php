<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Utility;

final class PageUtility
{
    /**
     * @param array<int|string> $page
     * @return int
     */
    public static function extractPageId(array $page): int
    {
        return $page['_LOCALIZED_UID'] ?? $page['uid'];
    }

    /**
     * @param array<int|string> $page
     * @return string
     */
    public static function extractPagePassword(array $page): string
    {
        return $page['tx_pagepassword_password'] ?? '';
    }

    /**
     * @param array<int|string> $page
     * @return int
     */
    public static function extractPagePasswordChangedAt(array $page): int
    {
        return $page['tx_pagepassword_password_changed_at'] ?? 0;
    }

    /**
     * @param array<int, array<int|string>> $rootLine
     * @return bool
     */
    public static function hasActiveProtection(array $rootLine): bool
    {
        $protectedPage = self::findProtectedPageInRootLine($rootLine);
        return !empty($protectedPage);
    }

    /**
     * @param array<int, array<int|string>> $rootLine
     * @return array<int|string>
     */
    public static function findProtectedPageInRootLine(array $rootLine): array
    {
        $currentPage = self::extractCurrentPageFromRootLine($rootLine);
        if (self::hasPageProtection($currentPage)) {
            return $currentPage;
        }

        return self::findInheritedProtectionInRootLine($rootLine);
    }

    /**
     * @param array<int, array<int|string>> $rootLine
     * @return array<int|string>
     */
    public static function extractCurrentPageFromRootLine(array $rootLine): array
    {
        return array_values($rootLine)[0] ?? [];
    }

    /**
     * @param array<int, array<int|string>> $rootLine
     * @return array<int|string>
     */
    public static function findInheritedProtectionInRootLine(array $rootLine): array
    {
        foreach ($rootLine as $page) {
            if (self::hasPageProtection($page) && self::extendsProtectionToSubpages($page)) {
                return $page;
            }
        }

        return [];
    }

    /**
     * @param array<int|string> $page
     * @return bool
     */
    public static function hasPageProtection(array $page): bool
    {
        return isset($page['tx_pagepassword_enable']) && $page['tx_pagepassword_enable'];
    }

    /**
     * @param array<int|string> $page
     * @return bool
     */
    public static function extendsProtectionToSubpages(array $page): bool
    {
        return isset($page['tx_pagepassword_extend_to_subpages']) && $page['tx_pagepassword_extend_to_subpages'];
    }
}
