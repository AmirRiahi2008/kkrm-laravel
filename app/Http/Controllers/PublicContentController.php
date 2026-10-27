<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexRequest;
use App\Services\ContentPresenter;
use App\Services\PublicQuery;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

final class PublicContentController
{
    public function index(IndexRequest $request, string $resource, PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $filters = $request->validated();
        $page = $queries->filter($queries->for($resource), $resource, $filters)->paginate($filters['per_page'] ?? 12);

        return ApiResponse::paginated($page, fn (Model $model) => $present->one($model, false, false));
    }

    public function show(string $resource, string $key, PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $query = $queries->for($resource);
        $hasSlug = in_array('slug', $query->getModel()->getFillable(), true);
        $numericKey = preg_match('/^[1-9][0-9]{0,18}$/D', $key) === 1;
        $model = $hasSlug ? (clone $query)->where('slug', $key)->first() : null;

        if ($model === null && $numericKey) {
            $model = $query->whereKey($key)->first();
        }

        abort_if($model === null, 404);

        return ApiResponse::success($present->one($model));
    }
}
