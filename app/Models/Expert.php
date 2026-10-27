<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expert extends SiteModel
{
    protected $with = ['photo'];

    protected $fillable = ['discipline_id', 'photo_id', 'first_name', 'last_name', 'license_number', 'city', 'geographical_scope', 'phone', 'email', 'show_contact', 'is_published', 'status', 'biography'];

    protected function casts(): array
    {
        return [
            'show_contact' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function qualifications(): BelongsToMany
    {
        return $this->belongsToMany(Qualification::class);
    }
}
