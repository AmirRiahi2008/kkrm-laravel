<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends SiteModel
{
    protected $fillable = ['location', 'display_mode', 'title', 'url', 'page_id', 'parent_id', 'open_new_tab', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'open_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class)->select(['id', 'slug', 'path', 'status', 'published_at']);
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id');
    }
}
