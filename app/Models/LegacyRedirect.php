<?php

declare(strict_types=1);

namespace App\Models;

class LegacyRedirect extends SiteModel
{
    protected $fillable = ['old_path', 'new_path', 'status_code', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
