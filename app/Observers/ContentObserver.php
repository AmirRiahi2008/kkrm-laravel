<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\AdminNotificationService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class ContentObserver
{
    public function saved(Model $model): void
    {
        $this->invalidate();
        app(AdminNotificationService::class)->record($model);
        app(AuditService::class)->record($model->wasRecentlyCreated ? 'content.created' : 'content.updated', $model, [
            'fields' => array_values(array_diff(array_keys($model->getChanges()), ['password', 'remember_token', 'session_version'])),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate();
        app(AuditService::class)->record('content.deleted', $model);
    }

    public function restored(Model $model): void
    {
        $this->invalidate();
        app(AuditService::class)->record('content.restored', $model);
    }

    private function invalidate(): void
    {
        foreach (['site.home', 'site.settings', 'site.navigation', 'site.home.fa', 'site.home.en', 'site.settings.fa', 'site.settings.en', 'site.navigation.fa', 'site.navigation.en'] as $key) {
            Cache::forget($key);
        }
    }
}
