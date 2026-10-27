<?php

declare(strict_types=1);

namespace App\Models;

class Page extends SiteModel
{
    protected $with = ['cover'];

    protected $fillable = ['show_in_slideshow', 'title', 'slug', 'path', 'summary', 'body', 'cover_id', 'status', 'published_at', 'meta_title', 'meta_description'];

    public function publicPath(): string
    {
        return $this->path ?: '/pages/'.rawurlencode($this->slug);
    }

    public function isPublic(): bool
    {
        return ! $this->trashed() && $this->status === 'published' && $this->published_at !== null && $this->published_at->lessThanOrEqualTo(now());
    }

    protected function casts(): array
    {
        return [
            'show_in_slideshow' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
