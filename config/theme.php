<?php

declare(strict_types=1);

return [
    'active' => env('THEME_ACTIVE', 'default'),
    'path' => resource_path('themes'),
    'cache_seconds' => (int) env('THEME_CACHE_SECONDS', 3600),
    'allow_db_overrides' => true,
    'fallback_tokens' => [
        'primary' => '#f97316',
        'primary-hover' => '#ea580c',
        'primary-light' => 'rgba(249, 115, 22, 0.12)',
        'primary-shadow' => 'rgba(249, 115, 22, 0.25)',
        'accent' => '#f59e0b',
        'accent-hover' => '#d97706',
        'mode' => 'dark',
    ],
];
