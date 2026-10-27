<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardMember extends SiteModel
{
    protected $with = ['photo'];

    protected $fillable = ['board_term_id', 'photo_id', 'name', 'position', 'role', 'biography', 'contact_label', 'contact_url', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(BoardTerm::class, 'board_term_id');
    }
}
