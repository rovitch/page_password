<?php

use Rovitch\PagePassword\Controller\AuthController;
use Rovitch\PagePassword\Hooks\PagePasswordUpdateHook;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

(static function (string $extensionName): void {
    ExtensionUtility::configurePlugin(
        $extensionName,
        'Form',
        [
            AuthController::class => 'form, login',
        ],
        // non-cacheable actions
        [
            AuthController::class => 'form, login',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    // Add additional page fields to rootLine, not necessary in v13
    if (version_compare((new Typo3Version())->getMajorVersion(), '13', '<')) {
        $addRootLineFields = $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'];
        $addRootLineFields = explode(',', $addRootLineFields);
        $addRootLineFields = array_merge(
            $addRootLineFields,
            [
                'tx_pagepassword_enable',
                'tx_pagepassword_extend_to_subpages',
                'tx_pagepassword_password',
                'tx_pagepassword_password_changed_at',
            ],
        );
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = implode(',', $addRootLineFields);
    }

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
        PagePasswordUpdateHook::class;

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        $extensionName,
        'setup',
        "@import 'EXT:page_password/Configuration/TypoScript/setup.typoscript'",
    );

})('PagePassword');
