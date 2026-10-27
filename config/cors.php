<?php

declare(strict_types=1);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type', 'X-XSRF-TOKEN', 'X-Requested-With', 'If-Match'],
    'exposed_headers' => ['X-Request-ID', 'Retry-After'],
    'max_age' => 600,
    'supports_credentials' => true,
];
