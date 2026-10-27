<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\ContentPresenter;
use App\Services\PublicQuery;
use App\Services\TranslationService;
use App\Support\ApiResponse;
use App\Support\ContentCatalog;
use Illuminate\Http\JsonResponse;

final class SlideshowController
{
    public function __invoke(PublicQuery $queries, ContentPresenter $present, TranslationService $translations): JsonResponse
    {
        $items = collect();

        foreach (ContentCatalog::PUBLISHED as $resource) {
            $imageField = $resource === 'documents' ? 'file_id' : 'cover_id';
            $columns = ['id', 'title', 'slug', 'published_at', $imageField];
            if (in_array($resource, ['posts', 'documents'], true)) {
                $columns[] = 'type';
            }
            if ($resource === 'pages') {
                $columns[] = 'path';
            }
            $models = $queries->for($resource)
                ->select($columns)
                ->where('show_in_slideshow', true)
                ->with($resource === 'documents' ? 'file' : 'cover')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get();

            foreach ($models as $model) {
                $media = $resource === 'documents' ? $model->file : $model->cover;
                $data = $model->only(['id', 'title', 'slug', 'type', 'published_at', 'path']);
                $data['cover'] = $media instanceof Media && str_starts_with($media->mime_type, 'image/')
                    ? $present->one($media)
                    : null;
                $data['slide_resource'] = $resource === 'posts'
                    ? ($model->type === 'article' ? 'articles' : 'news')
                    : $resource;
                $items->push($translations->present($model, $data, false));
            }
        }

        return ApiResponse::success($items->sort(function (array $first, array $second): int {
            return strcmp((string) $second['published_at'], (string) $first['published_at'])
                ?: strcmp($first['slide_resource'], $second['slide_resource'])
                ?: ($second['id'] <=> $first['id']);
        })->values()->all());
    }
}
