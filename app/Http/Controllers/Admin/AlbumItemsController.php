<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Album;
use App\Services\ContentPresenter;
use App\Services\ContentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class AlbumItemsController
{
    public function update(Request $request, Album $album, ContentService $service, ContentPresenter $present): JsonResponse
    {
        $data = $request->validate([
            'items' => ['present', 'array', 'max:100'],
            'items.*' => ['array:media_id,caption,caption_en,sort_order'],
            'items.*.media_id' => ['required', 'integer', 'distinct', Rule::exists('media', 'id')->whereNull('deleted_at')],
            'items.*.caption' => ['sometimes', 'nullable', 'string', 'max:250'],
            'items.*.caption_en' => ['sometimes', 'nullable', 'string', 'max:250'],
            'items.*.sort_order' => ['sometimes', 'integer', 'between:0,100000'],
        ]);
        foreach ($data['items'] as $item) {
            $service->validateMedia(
                (int) $item['media_id'],
                $request->user(),
                'image/'
            );
        }
        DB::transaction(function () use ($album, $data): void {
            $locked = Album::lockForUpdate()->findOrFail($album->id);
            $ids = array_column($data['items'], 'media_id');
            $locked->items()->whereNotIn('media_id', $ids)->get()->each->delete();
            foreach ($data['items'] as $index => $item) {
                $locked->items()->updateOrCreate(['media_id' => $item['media_id']], $item + ['sort_order' => $index]);
            }
        });

        return ApiResponse::success($present->one($album->refresh(), true));
    }
}
