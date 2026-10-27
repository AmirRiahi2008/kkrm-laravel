<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\SearchText;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends SiteModel
{
    protected $with = ['cover'];

    protected $hidden = ['search_text'];

    protected $fillable = ['show_in_slideshow', 'author_id', 'category_id', 'cover_id', 'type', 'title', 'slug', 'excerpt', 'body', 'status', 'published_at', 'expires_at', 'is_featured', 'is_pinned', 'meta_title', 'meta_description', 'lock_version'];

    protected function casts(): array
    {
        return [
            'show_in_slideshow' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if ($post->isDirty(['title', 'excerpt', 'body']) || $post->search_text === null) {
                $post->search_text = SearchText::fromPost($post->title, $post->excerpt, $post->body);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function attachments(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'post_media');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class);
    }
}
