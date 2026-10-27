<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Album;
use App\Models\Expert;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\ContentCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class ContentPresenter
{
    public function one(Model $model, bool $admin = false, bool $detail = true): array
    {
        if ($model instanceof User) {
            return $this->fields($model, ['id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at', 'updated_at']);
        }
        if ($model instanceof Media) {
            return [
                'id' => $model->id, 'url' => url('/api/v1/media/'.$model->id),
                'mime_type' => $model->mime_type, 'size' => $model->size, 'alt' => $model->alt,
            ] + ($admin ? $this->fields($model, ['original_name', 'uploaded_by', 'scan_status', 'created_at']) : []);
        }
        if ($model instanceof Post) {
            $model->loadMissing(['author', 'category', 'tags', 'cover']);
            $data = $this->fields($model, [
                'id', 'title', 'slug', 'type', 'excerpt', 'published_at', 'expires_at',
                'is_featured', 'is_pinned', 'meta_title', 'meta_description', 'created_at', 'updated_at',
            ]);
            $data['author'] = $model->author?->only(['id', 'name']);
            $data['category'] = $model->category?->is_active ? $this->one($model->category, $admin, false) : null;
            $data['tags'] = $model->tags->map(fn (Model $tag) => $this->one($tag, $admin, false))->values()->all();
            $data['cover'] = $model->cover ? $this->one($model->cover) : null;
            if ($detail) {
                $data['body'] = $model->body;
                $data['attachments'] = $model->attachments->map(fn (Media $m) => $this->one($m, $admin))->all();
            }
            if ($admin) {
                $data += $this->fields($model, ['author_id', 'category_id', 'cover_id', 'status', 'lock_version', 'show_in_slideshow', 'deleted_at']);
            }

            return app(TranslationService::class)->present($model, $data, $admin);
        }
        $fields = ContentCatalog::PUBLIC_FIELDS[$model::class] ?? $model->getFillable();
        $data = $this->fields($model, array_merge(['id'], $fields, ['created_at', 'updated_at']));
        if ($model instanceof Page) {
            $data['public_path'] = $model->publicPath();
            $data['is_public'] = $model->isPublic();
        }
        if ($model instanceof MenuItem && $model->page_id !== null) {
            $model->loadMissing('page');
            $data['url'] = $model->page?->publicPath();
        }
        if (! $detail && ! $admin) {
            unset($data['body'], $data['biography']);
        }
        foreach (['cover_id' => 'cover', 'file_id' => 'file', 'photo_id' => 'photo', 'image_id' => 'image'] as $field => $key) {
            if (isset($data[$field])) {
                $media = $model->relationLoaded($key) ? $model->getRelation($key) : Media::find($data[$field]);
                $data[$key] = $media ? $this->one($media, $admin) : null;
            }
        }
        if ($model instanceof Expert) {
            $model->loadMissing(['discipline.group', 'qualifications']);
            $data['discipline'] = $model->discipline ? $this->one($model->discipline, $admin, false) : null;
            $data['expert_group'] = $model->discipline?->group ? $this->one($model->discipline->group, $admin, false) : null;
            $data['qualifications'] = $model->qualifications->filter(fn (Model $q) => $admin || $q->is_active)->map(fn (Model $q) => $this->one($q, $admin, false))->values()->all();
            $data['phone'] = $admin || $model->show_contact ? $model->phone : null;
            $data['email'] = $admin || $model->show_contact ? $model->email : null;
        }
        if ($model instanceof Album && $detail) {
            $data['items'] = $model->items->map(function (Model $item) use ($admin): array {
                $media = Media::find($item->media_id);

                return ['id' => $item->id, 'caption' => ! $admin && TranslationService::locale() === 'en' && filled($item->caption_en) ? $item->caption_en : $item->caption, 'caption_en' => $admin ? $item->caption_en : null, 'sort_order' => $item->sort_order, 'media' => $media ? $this->one($media, $admin) : null];
            })->all();
        }
        if ($admin) {
            if (in_array('show_in_slideshow', $model->getFillable(), true)) {
                $data['show_in_slideshow'] = (bool) $model->show_in_slideshow;
            }
            $data['deleted_at'] = $model->attributesToArray()['deleted_at'] ?? null;
        }

        return app(TranslationService::class)->present($model, $data, $admin);
    }

    private function fields(Model $model, array $fields): array
    {
        return Arr::only($model->attributesToArray(), $fields);
    }
}
