<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

(static function (): void {
    $indexPluginSignature = ExtensionUtility::registerPlugin(
        'PagePassword',
        'Form',
        'LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:plugin.page_password_form',
        'EXT:page_password/Resources/Public/Icons/Extension.svg',
        'forms',
        'LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:plugin.page_password_form.description',
    );

    ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;Configuration,pi_flexform,', $indexPluginSignature, 'after:subheader');
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:page_password/Configuration/FlexForms/PagePasswordForm.xml',
        'pagepassword_form',
    );

    $GLOBALS['TCA']['tt_content']['types']['pagepassword_form'] = array_replace_recursive(
        $GLOBALS['TCA']['tt_content']['types']['pagepassword_form'],
        [
            'showitem' => '
                    --div--;General,
                    --palette--;General;general,
                    --div--;LLL:EXT:page_password/Resources/Private/Language/locallang.xlf:plugin.page_password_form.settings.tab.configuration,
                    pi_flexform',
        ],
    );
})();
