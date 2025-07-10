<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Page Password',
    'description' => 'PagePassword provides a simple way to restrict access to specific pages and their sub-pages with password authentication.',
    'state' => 'stable',
    'author' => 'rovitch',
    'author_email' => '',
    'category' => 'fe',
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
