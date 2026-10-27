<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\IndexRequest;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Support\ApiResponse;
use App\Support\ContentCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController
{
    public function show(Request $request): JsonResponse
    {
        $query = Post::query();
        if (! $request->user()->role->managesContent()) {
            $query->where('author_id', $request->user()->id);
        }
        $counts = [];
        foreach (['draft', 'review', 'published', 'archived'] as $status) {
            $counts[$status] = (clone $query)->where('status', $status)->count();
        }
        $modules = [];
        if ($request->user()->role->managesContent()) {
            foreach (ContentCatalog::MODELS as $resource => $class) {
                $modules[$resource] = $class::count();
            }
        }

        $recent = (clone $query)->latest('updated_at')->limit(6)->get()->map(fn (Post $post) => $post->only(['id', 'title', 'type', 'status', 'updated_at']))->all();
        $activity = [];
        for ($days = 6; $days >= 0; $days--) {
            $date = now()->subDays($days);
            $activity[] = ['date' => $date->toDateString(), 'count' => (clone $query)->whereDate('created_at', $date)->count()];
        }

        return ApiResponse::success(['posts' => $counts, 'modules' => $modules, 'recent_posts' => $recent, 'activity' => $activity, 'new_messages' => $request->user()->role->managesContent() ? ContactMessage::where('status', 'new')->count() : null]);
    }

    public function audits(IndexRequest $request): JsonResponse
    {
        return ApiResponse::paginated(AuditLog::latest('id')->paginate($request->validated('per_page', 15)), fn (AuditLog $log) => $log->only(['id', 'actor_id', 'action', 'subject_type', 'subject_id', 'metadata', 'created_at']));
    }
}
