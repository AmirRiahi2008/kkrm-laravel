<?php

declare(strict_types=1);

return [
    'proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
];
