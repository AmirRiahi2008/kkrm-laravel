<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tariff;
use Illuminate\Validation\ValidationException;

final class TariffService
{
    public function validateRules(array $rules): void
    {
        $previous = 0;
        $tiers = $rules['tiers'] ?? [];
        foreach ($tiers as $index => $tier) {
            $upper = $tier['up_to'];
            if (($upper === null && $index !== count($tiers) - 1) || ($upper !== null && $upper <= $previous)) {
                throw ValidationException::withMessages(['rules.tiers' => 'سقف پله‌ها باید صعودی باشد و فقط پله آخر می‌تواند نامحدود باشد.']);
            }
            $this->scaledRate($tier['rate_bps']);
            $previous = $upper ?? $previous;
        }
        if ($tiers === [] || $tiers[array_key_last($tiers)]['up_to'] !== null) {
            throw ValidationException::withMessages(['rules.tiers' => 'پله آخر باید نامحدود باشد.']);
        }
        if (($rules['maximum_fee'] ?? null) !== null && $rules['maximum_fee'] < $rules['minimum_fee']) {
            throw ValidationException::withMessages(['rules.maximum_fee' => 'سقف دستمزد نباید از حداقل کمتر باشد.']);
        }
        foreach (range(1, 5) as $count) {
            $multiplier = $rules['panel_multipliers'][$count] ?? null;
            if (! is_numeric($multiplier) || (int) $multiplier != $multiplier || $multiplier < 10000 || $multiplier > 50000) {
                throw ValidationException::withMessages(['rules.panel_multipliers' => 'ضریب هر هیئت باید عدد صحیح بین ۱۰۰۰۰ و ۵۰۰۰۰ باشد.']);
            }
        }
        if ((int) $rules['panel_multipliers'][1] !== 10000) {
            throw ValidationException::withMessages(['rules.panel_multipliers' => 'ضریب یک کارشناس باید ۱۰۰۰۰ باشد.']);
        }
    }

    public function calculate(Tariff $tariff, int $value, int $experts): array
    {
        if ($value < 1 || $value > 1000000000000000 || $experts < 1 || $experts > 5) {
            throw ValidationException::withMessages(['value' => 'ارزش ارزیابی یا تعداد کارشناسان معتبر نیست.']);
        }
        $rules = $tariff->rules;
        $this->validateRules($rules);
        $total = (int) $rules['base_fee'];
        $lower = 0;
        $segments = [];
        foreach ($rules['tiers'] as $tier) {
            $upper = $tier['up_to'] ?? $value;
            $amount = max(0, min($value, $upper) - $lower);
            $fee = $this->ceilRatio($amount, $this->scaledRate($tier['rate_bps']), 10000000);
            if ($amount > 0) {
                $segments[] = ['from' => $lower, 'to' => min($value, $upper), 'value' => $amount, 'rate_bps' => $tier['rate_bps'], 'fee' => $fee];
            }
            $total += $fee;
            $lower = $upper;
            if ($lower >= $value) {
                break;
            }
        }
        $multiplier = (int) $rules['panel_multipliers'][$experts];
        $total = $this->ceilRatio($total, $multiplier, 10000);
        $total = max($total, (int) $rules['minimum_fee']);
        if ($rules['maximum_fee'] !== null) {
            $total = min($total, (int) $rules['maximum_fee']);
        }

        return [
            'tariff_id' => $tariff->id,
            'tariff_name' => $tariff->name,
            'currency' => $tariff->currency,
            'value' => $value,
            'expert_count' => $experts,
            'base_fee' => $rules['base_fee'],
            'panel_multiplier_bps' => $multiplier,
            'segments' => $segments,
            'total' => $total,
            'is_demo' => $tariff->is_demo,
            'is_estimate' => true,
            'notice' => $tariff->is_demo ? 'محاسبه آزمایشی است و اعتبار قانونی ندارد.' : 'برآورد براساس نسخه انتخاب‌شده است؛ شرایط پرونده باید بررسی شود.',
        ];
    }

    private function scaledRate(int|float|string $rate): int
    {
        $value = (string) $rate;
        if (! preg_match('/\A([0-9]{1,5})(?:\.([0-9]{1,3}))?\z/', $value, $matches)) {
            throw ValidationException::withMessages(['rules.tiers' => 'نرخ باید عدد نامنفی با حداکثر سه رقم اعشار باشد.']);
        }
        $scaled = (int) $matches[1] * 1000 + (int) str_pad($matches[2] ?? '', 3, '0');
        if ($scaled > 10000000) {
            throw ValidationException::withMessages(['rules.tiers' => 'نرخ از محدوده مجاز بیشتر است.']);
        }

        return $scaled;
    }

    private function ceilRatio(int $amount, int $numerator, int $denominator): int
    {
        return intdiv($amount, $denominator) * $numerator
            + intdiv(($amount % $denominator) * $numerator + $denominator - 1, $denominator);
    }
}
