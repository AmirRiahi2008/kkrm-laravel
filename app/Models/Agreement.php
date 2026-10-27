<?php

declare(strict_types=1);

namespace App\Models;

class Agreement extends SiteModel
{
    protected $with = ['cover', 'file'];

    protected $fillable = ['show_in_slideshow', 'title', 'slug', 'partner', 'body', 'file_id', 'cover_id', 'starts_at', 'ends_at', 'is_demo', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'show_in_slideshow' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'published_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }
}
