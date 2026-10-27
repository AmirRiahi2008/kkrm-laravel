<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends SiteModel
{
    protected $table = 'media';

    protected $fillable = ['uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size', 'sha256', 'alt', 'scan_status'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
