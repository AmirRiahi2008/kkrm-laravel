<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['post_id', 'editor_id', 'snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'created_at' => 'datetime'];
    }
}
