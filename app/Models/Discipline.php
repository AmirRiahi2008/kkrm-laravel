<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends SiteModel
{
    protected $fillable = ['expert_group_id', 'name', 'code', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ExpertGroup::class, 'expert_group_id');
    }

    public function experts(): HasMany
    {
        return $this->hasMany(Expert::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class);
    }
}
