<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $user->notifications()->latest()->limit(30)->get()->map(fn ($item) => [
            'id' => $item->id, 'title' => $item->data['title'] ?? '', 'message' => $item->data['message'] ?? '',
            'url' => $item->data['url'] ?? '/admin', 'kind' => $item->data['kind'] ?? 'info',
            'read_at' => $item->read_at?->toIso8601String(), 'created_at' => $item->created_at->toIso8601String(),
        ])->all();

        return ApiResponse::success(['items' => $items, 'unread_count' => $user->unreadNotifications()->count()]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $item = $request->user()->notifications()->findOrFail($id);
        $item->markAsRead();

        return ApiResponse::success(['id' => $item->id]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return ApiResponse::success(['read' => true]);
    }
}