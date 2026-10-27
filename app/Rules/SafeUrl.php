<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SafeUrl implements ValidationRule
{
    public function __construct(private readonly bool $allowRelative = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/[\\\\\x00-\x20]/', $value)) {
            $fail('نشانی معتبر نیست.');

            return;
        }
        if ($this->allowRelative && str_starts_with($value, '/')) {
            $decoded = rawurldecode($value);
            if (str_starts_with($decoded, '//') || preg_match('/[\\\\\x00-\x20]/', $decoded)) {
                $fail('مسیر داخلی معتبر نیست.');
            }

            return;
        }
        $parts = parse_url($value);
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('نشانی باید یک URL معتبر با http یا https باشد.');
        }
    }
}
