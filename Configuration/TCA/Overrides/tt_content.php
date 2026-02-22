<?php

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

(static function (): void {
    if (version_compare((string)(new Typo3Version())->getMajorVersion(), '14', '>=')) {
        // @phpstan-ignore arguments.count
        $indexPluginSignature = ExtensionUtility::registerPlugin(
            'PagePassword',
            'Form',
            'LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:plugin.page_password_form',
            'EXT:page_password/Resources/Public/Icons/Extension.svg',
            'forms',
            'LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:plugin.page_password_form.description',
        );
    } else {
        $indexPluginSignature = ExtensionUtility::registerPlugin(
            'PagePassword',
            'Form',
            'LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:plugin.page_password_form',
            'EXT:page_password/Resources/Public/Icons/Extension.svg',
            'forms',
            'LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:plugin.page_password_form.description',
        );
        // @extensionScannerIgnoreLine
        ExtensionManagementUtility::addPiFlexFormValue(
            '*',
            'FILE:EXT:page_password/Configuration/FlexForms/PagePasswordForm.xml',
            'pagepassword_form',
        );
    }

    ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;Configuration,pi_flexform,', $indexPluginSignature, 'after:subheader');
})();
