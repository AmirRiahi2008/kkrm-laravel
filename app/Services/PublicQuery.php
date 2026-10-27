<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Support\ContentCatalog;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Builder;

final class PublicQuery
{
    public function for(string $resource): Builder
    {
        $query = $resource === 'posts' ? Post::query()->with(['author', 'category', 'tags', 'cover']) : ContentCatalog::model($resource)->newQuery();
        if (in_array($resource, ContentCatalog::PUBLISHED, true)) {
            $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
        } elseif ($resource === 'experts') {
            $query->whereIn('status', ['active', 'suspended'])->where('is_published', true)
                ->whereHas('discipline', fn (Builder $q) => $q->where('is_active', true)->whereHas('group', fn (Builder $g) => $g->where('is_active', true)))
                ->with(['discipline.group', 'qualifications' => fn ($q) => $q->where('is_active', true)]);
        } elseif (in_array('is_active', ContentCatalog::model($resource)->getFillable(), true)) {
            $query->where('is_active', true);
        }
        if ($resource === 'posts') {
            $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        }
        if ($resource === 'disciplines') {
            $query->whereHas('group', fn (Builder $q) => $q->where('is_active', true));
        }
        if ($resource === 'qualifications') {
            $query->whereIn('discipline_id', $this->for('disciplines')->select('id'));
        }
        if ($resource === 'board-members') {
            $query->where(fn (Builder $q) => $q->whereNull('board_term_id')->orWhereIn('board_term_id', $this->for('board-terms')->select('id')));
        }
        if ($resource === 'slides') {
            $query->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        }
        if (in_array($resource, ['tariffs', 'bank-accounts'], true) && ! config('kkrm.demo_enabled')) {
            $query->where('is_demo', false);
        }

        return $query->with('localizedContent');
    }

    public function filter(Builder $query, string $resource, array $filters): Builder
    {
        $fields = $query->getModel()->getFillable();
        $searchColumns = array_values(array_intersect(['title', 'name', 'question', 'first_name', 'last_name', 'license_number'], $fields));
        if ($resource === 'posts') {
            $searchColumns = ['search_text'];
        }
        if (! empty($filters['q']) && $searchColumns !== []) {
            $q = str_replace('\\', '\\\\', $this->normalize($filters['q']));
            $query->where(function (Builder $query) use ($q, $searchColumns): void {
                if (TranslationService::locale() === 'en') {
                    $query->orWhereHas('localizedContent', fn (Builder $translated) => $translated->where('locale', 'en')->where('search_text', 'like', '%'.$q.'%'));
                }
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'like', '%'.$q.'%');
                }
            });
        }
        foreach (['type', 'group', 'role', 'discipline_id', 'expert_group_id', 'board_term_id', 'license_number', 'city', 'geographical_scope'] as $key) {
            if (isset($filters[$key]) && in_array($key, $fields, true)) {
                $query->where($key, $filters[$key]);
            }
        }
        if ($resource === 'experts' && isset($filters['expert_group_id'])) {
            $query->whereHas('discipline', fn (Builder $q) => $q->where('expert_group_id', $filters['expert_group_id']));
        }
        if ($resource === 'experts') {
            if (isset($filters['qualification_id'])) {
                $query->whereHas('qualifications', fn (Builder $q) => $q->whereKey($filters['qualification_id'])->where('is_active', true));
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        }
        foreach (['first_name', 'last_name'] as $key) {
            if (isset($filters[$key]) && in_array($key, $fields, true)) {
                $query->where($key, 'like', '%'.$this->normalize($filters[$key]).'%');
            }
        }
        if ($resource === 'posts') {
            if (! empty($filters['category'])) {
                $query->whereHas('category', fn (Builder $q) => $q->where('slug', $filters['category'])->where('is_active', true));
            }
            if (! empty($filters['tag'])) {
                $query->whereHas('tags', fn (Builder $q) => $q->where('slug', $filters['tag']));
            }
        }
        if (in_array('published_at', $fields, true)) {
            foreach (['from' => '>=', 'to' => '<='] as $key => $op) {
                if (isset($filters[$key])) {
                    $query->whereDate('published_at', $op, $filters[$key]);
                }
            }
        }
        $sort = $filters['sort'] ?? 'latest';
        if ($sort === 'title' && in_array('title', $fields, true)) {
            $query->orderBy('title');
        } elseif (in_array('sort_order', $fields, true)) {
            $query->orderBy('sort_order');
        } else {
            if ($resource === 'posts') {
                $query->orderByDesc('is_pinned');
            }
            $query->orderBy(in_array('published_at', $fields, true) ? 'published_at' : 'created_at', $sort === 'oldest' ? 'asc' : 'desc');
        }

        return $query->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc');
    }

    public function normalize(string $value): string
    {
        return SearchText::normalize($value);
    }
}
