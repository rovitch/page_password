<?php

use Rovitch\PagePassword\Middleware\AuthMiddleware;

return [
    'frontend' => [
        'rovitch/quick-auth-middleware' => [
            'target' => AuthMiddleware::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ],
];
