<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexRequest;
use App\Models\Media;
use App\Services\ContentPresenter;
use App\Services\MediaAccessService;
use App\Services\MediaService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaController
{
    public function index(IndexRequest $request, ContentPresenter $present): JsonResponse
    {
        $query = Media::query()->latest('id');
        if (! $request->user()->role->managesContent()) {
            $query->where('uploaded_by', $request->user()->id);
        }

        return ApiResponse::paginated($query->paginate($request->validated('per_page', 15)), fn (Media $media) => $present->one($media, true));
    }

    public function store(Request $request, MediaService $service, ContentPresenter $present): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.config('kkrm.media_max_kb')],
            'alt' => ['sometimes', 'nullable', 'string', 'max:250'],
        ]);
        $media = $service->upload($data['file'], $request->user(), $data['alt'] ?? null);

        return ApiResponse::success($present->one($media, true), 201);
    }

    public function show(Request $request, Media $media, MediaAccessService $access): StreamedResponse
    {
        $public = $access->public($media);
        if (! $public) {
            $user = $request->user('web');
            abort_unless($user && $user->is_active && $request->hasSession() && (int) $request->session()->get('auth.version') === $user->session_version && ($user->role->managesContent() || $media->uploaded_by === $user->id), 404);
        }
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);
        $response = Storage::disk($media->disk)->response(
            $media->path,
            'file-'.$media->id.'.'.pathinfo($media->path, PATHINFO_EXTENSION),
            ['Content-Type' => $media->mime_type, 'Cache-Control' => $public ? 'public, max-age=60' : 'private, no-store'],
            $media->mime_type === 'application/pdf' ? 'attachment' : 'inline',
        );

        return $response;
    }

    public function destroy(Request $request, Media $media, MediaAccessService $access): JsonResponse
    {
        abort_unless($request->user()->role->managesContent() || $media->uploaded_by === $request->user()->id, 403);
        abort_if($access->referenced($media), 409);
        $media->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
