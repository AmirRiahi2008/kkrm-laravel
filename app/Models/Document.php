<?php

declare(strict_types=1);

namespace App\Models;

class Document extends SiteModel
{
    protected $with = ['file'];

    protected $fillable = ['show_in_slideshow', 'title', 'slug', 'type', 'description', 'body', 'file_id', 'source_url', 'document_number', 'issued_at', 'is_demo', 'status', 'published_at', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'show_in_slideshow' => 'boolean',
            'issued_at' => 'date',
            'published_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }
}
