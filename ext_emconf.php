<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Page Password',
    'description' => 'PagePassword provides a simple way to restrict access to specific pages and their sub-pages with password authentication. This extension allows you to create password-protected sections or entire page trees within your TYPO3 website. Perfect for creating member areas, work-in-progress sections, pre-production environments, or any content that requires basic access control at the page level.',
    'state' => 'stable',
    'author' => 'rovitch',
    'author_email' => '',
    'category' => 'fe',
    'version' => '1.4.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
