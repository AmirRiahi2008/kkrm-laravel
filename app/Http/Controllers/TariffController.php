<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PublicQuery;
use App\Services\TariffService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class TariffController
{
    public function calculate(Request $request, TariffService $service, PublicQuery $queries): JsonResponse
    {
        $today = now('Asia/Tehran')->toDateString();
        $data = $request->validate([
            'tariff_id' => ['sometimes', 'integer', 'min:1'],
            'value' => ['required', 'integer', 'between:1,1000000000000000'],
            'expert_count' => ['sometimes', 'integer', 'between:1,5'],
            'date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:'.$today],
        ]);
        $date = $data['date'] ?? $today;
        $query = $queries->for('tariffs')->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date));
        if (isset($data['tariff_id'])) {
            $query->whereKey($data['tariff_id']);
        }
        $tariff = $query->orderByDesc('effective_from')->orderByDesc('id')->first();
        if (! $tariff) {
            throw ValidationException::withMessages(['tariff_id' => 'برای این تاریخ، نسخه فعال تعرفه ثبت نشده است.']);
        }

        return ApiResponse::success($service->calculate($tariff, (int) $data['value'], (int) ($data['expert_count'] ?? 1)));
    }
}
