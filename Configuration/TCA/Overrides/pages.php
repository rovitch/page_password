<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_pagepassword_enable' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:page_password/Resources/Private/Language/locallang_db.xlf:pages.tx_pagepassword_enable',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_pagepassword_extend_to_subpages' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:page_password/Resources/Private/Language/locallang_db.xlf:pages.tx_pagepassword_extend_to_subpages',
            'displayCond' => 'FIELD:tx_pagepassword_enable:REQ:true',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'tx_pagepassword_password' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:page_password/Resources/Private/Language/locallang_db.xlf:pages.tx_pagepassword_password',
            'displayCond' => 'FIELD:tx_pagepassword_enable:REQ:true',
            'config' => [
                'type' => 'password',
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'LLL:EXT:page_password/Resources/Private/Language/locallang_db.xlf:pages.tx_pagepassword_password.random',
                            'passwordRules' => [
                                'specialCharacters' => true,
                            ],
                        ],
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'fieldWizard' => [
                    'localizationStateSelector' => [
                        'renderType' => 'localizationStateSelector',
                    ],
                ],
                'required' => true,
            ],
        ],
        'tx_pagepassword_password_changed_at' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
);
ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:page_password/Resources/Private/Language/locallang_db.xlf:pages.tx_pagepassword.title,tx_pagepassword_enable,tx_pagepassword_extend_to_subpages,tx_pagepassword_password',
    '1',
);
