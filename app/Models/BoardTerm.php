<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardTerm extends SiteModel
{
    protected $fillable = ['title', 'started_at', 'ended_at', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(BoardMember::class);
    }
}
