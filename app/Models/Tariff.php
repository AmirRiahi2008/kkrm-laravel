<?php

declare(strict_types=1);

namespace App\Models;

class Tariff extends SiteModel
{
    protected $fillable = ['name', 'slug', 'currency', 'effective_from', 'effective_to', 'source_url', 'approval_reference', 'rules', 'is_demo', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'rules' => 'array',
            'is_demo' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
