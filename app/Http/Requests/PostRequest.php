<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Post;
use App\Services\TranslationService;
use App\Support\ContentTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_active;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $post = $this->route('post');
        $id = $post instanceof Post ? $post->id : $post;

        return [
            'title' => [$required, 'string', 'max:250'],
            'slug' => [$required, 'string', 'max:180', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique('posts')->ignore($id)],
            'type' => [$required, Rule::in(ContentTypes::POSTS)],
            'body' => [$required, 'string', 'max:100000'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::in(ContentTypes::POST_STATUSES)],
            'category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'cover_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media', 'id')->whereNull('deleted_at')],
            'tag_ids' => ['sometimes', 'array', 'max:20'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists('tags', 'id')->whereNull('deleted_at')],
            'attachment_ids' => ['sometimes', 'array', 'max:20'],
            'attachment_ids.*' => ['integer', 'distinct', Rule::exists('media', 'id')->whereNull('deleted_at')],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_pinned' => ['sometimes', 'boolean'],
            'show_in_slideshow' => $this->user()?->role->managesContent() ? ['sometimes', 'boolean'] : ['prohibited'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:250'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'lock_version' => [$this->isMethod('POST') ? 'prohibited' : 'required', 'integer', 'min:1'],
            'author_id' => ['prohibited'],
        ] + TranslationService::rules(new Post);
    }
}
