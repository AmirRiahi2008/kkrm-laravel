<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ContentRequest;
use App\Http\Requests\IndexRequest;
use App\Services\ContentPresenter;
use App\Services\ContentService;
use App\Services\PublicQuery;
use App\Support\ApiResponse;
use App\Support\ContentCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

final class ContentController
{
    public function index(IndexRequest $request, string $resource, PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $query = ContentCatalog::model($resource)->newQuery();
        if ($request->boolean('trash')) {
            $query->onlyTrashed();
        }
        $filters = $request->validated();
        if (isset($filters['status']) && in_array('status', $query->getModel()->getFillable(), true)) {
            $query->where('status', $filters['status']);
        }
        $page = $queries->filter($query, $resource, $filters)->paginate($filters['per_page'] ?? 15);

        return ApiResponse::paginated($page, fn (Model $model) => $present->one($model, true));
    }

    public function store(ContentRequest $request, string $resource, ContentService $service, ContentPresenter $present): JsonResponse
    {
        $model = $service->save(ContentCatalog::model($resource), $request->validated(), $request->user());

        return ApiResponse::success($present->one($model, true), 201);
    }

    public function show(string $resource, int $item, ContentPresenter $present): JsonResponse
    {
        $model = ContentCatalog::model($resource)->newQuery()->findOrFail($item);

        return ApiResponse::success($present->one($model, true));
    }

    public function update(ContentRequest $request, string $resource, int $item, ContentService $service, ContentPresenter $present): JsonResponse
    {
        $model = ContentCatalog::model($resource)->newQuery()->findOrFail($item);
        $model = $service->save($model, $request->validated(), $request->user());

        return ApiResponse::success($present->one($model, true));
    }

    public function destroy(string $resource, int $item): JsonResponse
    {
        $model = ContentCatalog::model($resource)->newQuery()->findOrFail($item);
        $model->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    public function restore(string $resource, int $item, ContentPresenter $present): JsonResponse
    {
        $model = ContentCatalog::model($resource)->newQuery()->onlyTrashed()->findOrFail($item);
        if (in_array('status', $model->getFillable(), true) && $resource !== 'experts') {
            $model->status = 'draft';
        }
        $model->restore();

        return ApiResponse::success($present->one($model, true));
    }
}
