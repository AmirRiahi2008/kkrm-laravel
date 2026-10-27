<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\IndexRequest;
use App\Models\ContactMessage;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ContactMessageController
{
    public function index(IndexRequest $request): JsonResponse
    {
        $query = ContactMessage::query()->latest('id');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return ApiResponse::paginated($query->paginate($request->validated('per_page', 15)), fn ($m) => $m->only(['id', 'name', 'email', 'phone', 'subject', 'status', 'created_at']));
    }

    public function show(ContactMessage $contactMessage): JsonResponse
    {
        return ApiResponse::success($contactMessage->only(['id', 'name', 'email', 'phone', 'subject', 'message', 'status', 'internal_note', 'created_at', 'updated_at']));
    }

    public function update(Request $request, ContactMessage $contactMessage, AuditService $audit): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['new', 'read', 'closed'])],
            'internal_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        $contactMessage->update($data);
        $audit->record('contact.updated', $contactMessage, ['fields' => array_keys($data)]);

        return $this->show($contactMessage);
    }

    public function destroy(ContactMessage $contactMessage, AuditService $audit): JsonResponse
    {
        $audit->record('contact.deleted', $contactMessage);
        $contactMessage->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
