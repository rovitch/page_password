<?php

$GLOBALS['SiteConfiguration']['site']['columns']['pagepassword_default_login_page'] = [
    'label' => 'LLL:EXT:page_password/Resources/Private/Language/locallang_db.xlf:sites.tx_pagepassword_default_login_page',
    'config' => [
        'type' => 'link',
        'allowedTypes' => ['page'],
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',--div--;LLL:EXT:page_password/Resources/Private/Language/locallang_db.xlf:sites.tx_pagepassword,pagepassword_default_login_page';
