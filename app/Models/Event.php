<?php

declare(strict_types=1);

namespace App\Models;

class Event extends SiteModel
{
    protected $with = ['cover'];

    protected $fillable = ['show_in_slideshow', 'title', 'slug', 'body', 'cover_id', 'mode', 'location', 'starts_at', 'ends_at', 'registration_url', 'status', 'published_at', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'show_in_slideshow' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
