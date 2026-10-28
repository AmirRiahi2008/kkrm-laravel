<?php

declare(strict_types=1);

return [
    'public_site_url' => env('PUBLIC_SITE_URL'),
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://127.0.0.1:8000')),
    'trusted_proxies' => array_values(array_filter(explode(',', env('TRUSTED_PROXIES', '')))),
    'media_disk' => env('MEDIA_DISK', 'local'),
    'legacy_media_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('MEDIA_LEGACY_ORIGINS', 'https://kkrm.vercel.app,https://kkrm-laravel.vercel.app,https://laravel-q2yx48.chbkn.dev'))))),
    'media_max_kb' => (int) env('MEDIA_MAX_KB', 51200),
    'clamav_enabled' => (bool) env('CLAMAV_ENABLED', false),
    'clamav_binary' => env('CLAMAV_BINARY', 'clamscan'),
    'demo_enabled' => env('APP_ENV', 'production') === 'local',
    'contact_retention_days' => (int) env('CONTACT_RETENTION_DAYS', 180),
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
