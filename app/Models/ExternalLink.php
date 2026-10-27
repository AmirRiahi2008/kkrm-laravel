<?php

declare(strict_types=1);

namespace App\Models;

class ExternalLink extends SiteModel
{
    protected $fillable = ['title', 'code', 'url', 'group', 'icon', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
