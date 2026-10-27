<?php

declare(strict_types=1);

return [
    'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
    'favicon_media_id' => ['nullable', 'integer', 'exists:media,id'],
    'site_name_en' => ['nullable', 'string', 'max:200'],
    'site_description_en' => ['nullable', 'string', 'max:1000'],
    'site_address_en' => ['nullable', 'string', 'max:1000'],
    'office_hours_en' => ['nullable', 'string', 'max:250'],
    'announcement_ticker_en' => ['nullable', 'string', 'max:500'],
    'copyright_en' => ['nullable', 'string', 'max:250'],
    'site_name' => ['string', 'max:200'],
    'site_description' => ['string', 'max:1000'],
    'site_email' => ['email', 'max:200'],
    'site_phone' => ['string', 'max:100'],
    'site_address' => ['string', 'max:1000'],
    'office_hours' => ['string', 'max:250'],
    'announcement_ticker' => ['string', 'max:500'],
    'map_latitude' => ['numeric', 'between:-90,90'],
    'map_longitude' => ['numeric', 'between:-180,180'],
    'copyright' => ['string', 'max:250'],
];
