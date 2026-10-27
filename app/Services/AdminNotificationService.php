<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ContentActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class AdminNotificationService
{
    public function record(Model $model): void
    {
        if ($model instanceof ContactMessage && $model->wasRecentlyCreated) {
            $payload = ['title' => 'پیام جدید از سایت', 'message' => 'یک پیام تازه برای روابط عمومی ثبت شده است.', 'url' => '/admin/contact-messages/'.$model->id, 'kind' => 'message'];
            $this->managers($payload);
        }
        if ($model instanceof Post && $model->status === 'review' && ($model->wasRecentlyCreated || $model->wasChanged('status'))) {
            $payload = ['title' => 'مطلب آماده بررسی', 'message' => $model->title, 'url' => '/admin/posts/'.$model->id, 'kind' => 'review'];
            $this->managers($payload);
        }
        if ($model instanceof Post && $model->status === 'published' && $model->wasChanged('status')) {
            $authorId = $model->author_id;
            $payload = ['title' => 'مطلب شما منتشر شد', 'message' => $model->title, 'url' => '/admin/posts/'.$model->id, 'kind' => 'published'];
            DB::afterCommit(function () use ($authorId, $payload): void {
                $author = User::where('is_active', true)->find($authorId);
                if ($author?->role->value === 'editor') {
                    $author->notify(new ContentActivity($payload));
                }
            });
        }
    }

    private function managers(array $payload): void
    {
        DB::afterCommit(function () use ($payload): void {
            $users = User::where('is_active', true)->whereIn('role', ['super_admin', 'relations_manager'])->get();
            Notification::send($users, new ContentActivity($payload));
        });
    }
}
