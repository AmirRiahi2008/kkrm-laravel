<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\IndexRequest;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Services\ContentPresenter;
use App\Services\PostService;
use App\Services\PublicQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class PostController
{
    public function index(IndexRequest $request, PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $query = Post::query()->with(['author', 'category', 'tags', 'cover']);
        if (! $request->user()->role->managesContent()) {
            $query->where('author_id', $request->user()->id);
        }
        if ($request->boolean('trash')) {
            Gate::authorize('manage-content');
            $query->onlyTrashed();
        }
        $filters = $request->validated();
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $page = $queries->filter($query, 'posts', $filters)->paginate($filters['per_page'] ?? 15);

        return ApiResponse::paginated($page, fn (Post $post) => $present->one($post, true));
    }

    public function store(PostRequest $request, PostService $service, ContentPresenter $present): JsonResponse
    {
        return ApiResponse::success($present->one($service->create($request->validated(), $request->user()), true), 201);
    }

    public function show(Post $post, ContentPresenter $present): JsonResponse
    {
        Gate::authorize('view', $post);

        return ApiResponse::success($present->one($post, true));
    }

    public function update(PostRequest $request, Post $post, PostService $service, ContentPresenter $present): JsonResponse
    {
        return ApiResponse::success($present->one($service->update($post, $request->validated(), $request->user()), true));
    }

    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);
        $post->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    public function restore(int $id, ContentPresenter $present): JsonResponse
    {
        Gate::authorize('manage-content');
        $post = Post::onlyTrashed()->findOrFail($id);
        $post->status = 'draft';
        $post->lock_version++;
        $post->restore();

        return ApiResponse::success($present->one($post, true));
    }

    public function revisions(Post $post): JsonResponse
    {
        Gate::authorize('view', $post);

        return ApiResponse::paginated($post->revisions()->latest('id')->paginate(15), fn ($revision) => $revision->only(['id', 'editor_id', 'snapshot', 'created_at']));
    }
}
