<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\PagePath;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SafePagePath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PagePath::valid($value)) {
            $fail('نشانی صفحه باید مانند /services/consultation باشد. از حروف، عدد و خط تیره استفاده کنید؛ مسیرهای اصلی سایت رزرو هستند.');
        }
    }
}
