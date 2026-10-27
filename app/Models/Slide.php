<?php

declare(strict_types=1);

namespace App\Models;

class Slide extends SiteModel
{
    protected $with = ['image'];

    protected $fillable = ['title', 'image_id', 'target_url', 'starts_at', 'ends_at', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
