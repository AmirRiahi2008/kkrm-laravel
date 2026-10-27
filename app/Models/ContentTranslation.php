<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentTranslation extends Model
{
    protected $fillable = ['resource', 'record_id', 'locale', 'fields', 'search_text'];

    protected function casts(): array
    {
        return ['fields' => 'array'];
    }
}
