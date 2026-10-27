<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpertGroup extends SiteModel
{
    protected $fillable = ['name', 'slug', 'code', 'description', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function disciplines(): HasMany
    {
        return $this->hasMany(Discipline::class);
    }
}
