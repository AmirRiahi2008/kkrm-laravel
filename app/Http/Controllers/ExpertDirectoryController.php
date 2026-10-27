<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexRequest;
use App\Services\ContentPresenter;
use App\Services\PublicQuery;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

final class ExpertDirectoryController
{
    public function filters(IndexRequest $request, PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $filters = $request->validated();
        $disciplines = $queries->for('disciplines');
        if (isset($filters['expert_group_id'])) {
            $disciplines->where('expert_group_id', $filters['expert_group_id']);
        }
        $qualifications = collect();
        if (isset($filters['discipline_id'])) {
            $qualifications = $queries->for('qualifications')
                ->where('discipline_id', $filters['discipline_id'])
                ->whereIn('discipline_id', (clone $disciplines)->select('id'))
                ->orderBy('sort_order')->orderBy('id')->get();
        }
        $map = fn (Model $model): array => $present->one($model, false, false);

        return ApiResponse::success([
            'groups' => $queries->for('expert-groups')->orderBy('sort_order')->orderBy('id')->get()->map($map)->values()->all(),
            'disciplines' => $disciplines->orderBy('sort_order')->orderBy('id')->get()->map($map)->values()->all(),
            'qualifications' => $qualifications->map($map)->values()->all(),
            'cities' => $queries->for('experts')->whereNotNull('city')->where('city', '!=', '')
                ->reorder()->select('city')->distinct()->orderBy('city')->pluck('city')->values()->all(),
            'statuses' => ['active', 'suspended'],
        ]);
    }
}
