<?php

declare(strict_types=1);

namespace App\Models;

class Faq extends SiteModel
{
    protected $fillable = ['question', 'answer', 'group', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
