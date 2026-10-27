<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use App\Services\ContentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SettingsController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(Setting::whereIn('key', array_keys(config('site-settings')))->get()->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])->all());
    }

    public function update(Request $request, ContentService $service): JsonResponse
    {
        $definitions = config('site-settings');
        $rules = ['settings' => ['required', 'array:'.implode(',', array_keys($definitions)), 'max:30']];
        foreach ($definitions as $key => $constraints) {
            $rules['settings.'.$key] = ['sometimes', ...$constraints];
        }
        $data = $request->validate($rules)['settings'];
        foreach (['logo_media_id', 'favicon_media_id'] as $key) {
            if (! empty($data[$key])) {
                $service->validateMedia((int) $data[$key], $request->user(), 'image/');
            }
        }
        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        return $this->index();
    }
}
