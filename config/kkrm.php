<?php

declare(strict_types=1);

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'trusted_proxies' => array_values(array_filter(explode(',', env('TRUSTED_PROXIES', '')))),
    'media_disk' => env('MEDIA_DISK', 'local'),
    'media_max_kb' => (int) env('MEDIA_MAX_KB', 51200),
    'clamav_enabled' => (bool) env('CLAMAV_ENABLED', false),
    'clamav_binary' => env('CLAMAV_BINARY', 'clamscan'),
    'demo_enabled' => env('APP_ENV', 'production') === 'local',
    'contact_retention_days' => (int) env('CONTACT_RETENTION_DAYS', 180),
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
