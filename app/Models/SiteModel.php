<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ContentCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class SiteModel extends Model
{
    use SoftDeletes;

    public function localizedContent(): HasMany
    {
        return $this->hasMany(ContentTranslation::class, 'record_id')->where('resource', ContentCatalog::resource($this));
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'file_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }
}
