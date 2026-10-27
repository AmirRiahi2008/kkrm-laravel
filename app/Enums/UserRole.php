<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case RelationsManager = 'relations_manager';
    case Editor = 'editor';

    public function managesContent(): bool
    {
        return in_array($this, [self::SuperAdmin, self::RelationsManager], true);
    }
}
