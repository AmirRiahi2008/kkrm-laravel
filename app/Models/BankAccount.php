<?php

declare(strict_types=1);

namespace App\Models;

class BankAccount extends SiteModel
{
    protected $fillable = ['bank_name', 'holder', 'account_number', 'iban', 'card_number', 'purpose', 'is_demo', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
