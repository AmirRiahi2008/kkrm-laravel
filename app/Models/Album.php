<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends SiteModel
{
    protected $with = ['cover'];

    protected $fillable = ['show_in_slideshow', 'title', 'slug', 'description', 'cover_id', 'type', 'event_date', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'show_in_slideshow' => 'boolean',
            'event_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AlbumItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
