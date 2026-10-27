<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

final class AuditService
{
    public function record(string $action, ?Model $subject = null, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip_hash' => app()->runningInConsole() ? null : hash_hmac('sha256', request()->ip() ?? '', config('app.key')),
        ]);
    }
}
