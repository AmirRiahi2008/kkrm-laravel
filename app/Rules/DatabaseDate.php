<?php

declare(strict_types=1);

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

final class DatabaseDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $date = is_string($value) ? CarbonImmutable::parse($value)->utc() : null;
        } catch (Throwable) {
            $date = null;
        }

        if ($date === null || $date->year < 1000 || $date->year > 9999) {
            $fail('تاریخ و زمان باید معتبر و بین سال‌های ۱۰۰۰ تا ۹۹۹۹ میلادی باشد.');
        }
    }
}
