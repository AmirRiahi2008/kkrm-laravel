<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\TranslationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContactController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:200'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[0-9 -]{7,20}$/'],
            'subject' => ['required', 'string', 'max:250'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['sometimes', 'nullable', 'string', 'max:250'],
        ]);
        if (empty($data['website'])) {
            unset($data['website']);
            $data['ip_hash'] = hash_hmac('sha256', $request->ip() ?? '', config('app.key'));
            ContactMessage::create($data);
        }

        return ApiResponse::success(['message' => TranslationService::locale() === 'en' ? 'Your message has been received.' : 'پیام شما ثبت شد.'], 201);
    }
}
