<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class PostService
{
    public function create(array $data, User $actor): Post
    {
        Gate::authorize('create', Post::class);

        return DB::transaction(function () use ($data, $actor): Post {
            $data['author_id'] = $actor->id;
            $data['status'] ??= 'draft';
            $this->validate($data, $actor);
            $post = Post::create(Arr::except($data, ['tag_ids', 'attachment_ids', 'translations']));
            $this->sync($post, $data);
            app(TranslationService::class)->save($post, $data['translations'] ?? null);

            return $post->refresh();
        });
    }

    public function update(Post $post, array $data, User $actor): Post
    {
        return DB::transaction(function () use ($post, $data, $actor): Post {
            $locked = Post::lockForUpdate()->findOrFail($post->id);
            Gate::authorize('update', $locked);
            abort_if($locked->lock_version !== (int) $data['lock_version'], 409);
            $candidate = array_merge($locked->getAttributes(), $data);
            $this->validate($data, $actor, $candidate);
            PostRevision::create([
                'post_id' => $locked->id, 'editor_id' => $actor->id,
                'snapshot' => $locked->only($locked->getFillable()) + [
                    'translations' => ['en' => $locked->localizedContent->firstWhere('locale', 'en')?->fields ?? []],
                    'tag_ids' => $locked->tags()->pluck('tags.id')->all(),
                    'attachment_ids' => $locked->attachments()->pluck('media.id')->all(),
                ],
            ]);
            $data['lock_version'] = $locked->lock_version + 1;
            $locked->fill(Arr::except($data, ['tag_ids', 'attachment_ids', 'translations']))->save();
            $this->sync($locked, $data);
            app(TranslationService::class)->save($locked, $data['translations'] ?? null);

            return $locked->refresh();
        });
    }

    private function validate(array &$data, User $actor, array $candidate = []): void
    {
        $candidate = array_merge($candidate, $data);
        if (! $actor->role->managesContent()) {
            abort_if(isset($data['status']) && ! in_array($data['status'], ['draft', 'review'], true), 403);
            abort_if(! empty($data['is_featured']) || ! empty($data['is_pinned']), 403);
            abort_if(array_key_exists('show_in_slideshow', $data), 403);
        }
        app(ContentService::class)->validateDates($candidate);
        if (! empty($data['cover_id'])) {
            app(ContentService::class)->validateMedia((int) $data['cover_id'], $actor, 'image/');
        }
        foreach ($data['attachment_ids'] ?? [] as $id) {
            app(ContentService::class)->validateMedia((int) $id, $actor);
        }
        if (array_key_exists('body', $data)) {
            $data['body'] = app(HtmlService::class)->clean($data['body']);
            if (
                trim(strip_tags($data['body'])) === ''
                && preg_match('/<img\b[^>]*\bsrc="[^"]+"/i', $data['body']) !== 1
            ) {
                throw ValidationException::withMessages(['body' => 'متن محتوا نباید خالی باشد.']);
            }
        }
        if (($candidate['status'] ?? null) === 'published' && empty($candidate['published_at'])) {
            $data['published_at'] = now();
        }
    }

    private function sync(Post $post, array $data): void
    {
        if (array_key_exists('tag_ids', $data)) {
            $post->tags()->sync($data['tag_ids']);
        }
        if (array_key_exists('attachment_ids', $data)) {
            $post->attachments()->sync($data['attachment_ids']);
        }
    }
}
