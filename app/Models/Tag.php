<?php

declare(strict_types=1);

namespace App\Models;

class Tag extends SiteModel
{
    protected $fillable = ['name', 'slug'];
}
