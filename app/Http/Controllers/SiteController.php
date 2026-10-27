<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\LegacyRedirect;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Services\ContentPresenter;
use App\Services\PublicQuery;
use App\Services\TranslationService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class SiteController
{
    public function home(PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $data = Cache::remember('site.home.'.TranslationService::locale(), 60, function () use ($queries, $present): array {
            $labels = [
                'news' => 'اخبار کانون', 'article' => 'مقاله‌ها', 'announcement' => 'اطلاعیه‌ها',
                'resolution' => 'مصوبات', 'training' => 'آموزش', 'welfare' => 'اخبار رفاهی',
                'condolence' => 'پیام‌های تسلیت', 'group_event' => 'رویدادهای گروه‌ها',
            ];
            if (TranslationService::locale() === 'en') {
                $labels = ['news' => 'Council news', 'article' => 'Articles', 'announcement' => 'Announcements', 'resolution' => 'Resolutions', 'training' => 'Training', 'welfare' => 'Welfare', 'condolence' => 'Condolences', 'group_event' => 'Expert group events'];
            }
            $sections = [];
            foreach ($labels as $type => $title) {
                $sections[] = [
                    'type' => $type, 'title' => $title, 'archive_path' => $type === 'article' ? '/articles' : '/news?type='.$type,
                    'items' => $queries->filter($queries->for('posts')->where('type', $type), 'posts', [])->limit(6)->get()->map(fn (Model $p) => $present->one($p, false, false))->all(),
                ];
            }

            $president = $queries->for('board-members')->where('role', 'board')->orderBy('sort_order')->get()->first(function (Model $member): bool {
                $position = str_replace(['ي', 'ئ'], ['ی', 'ی'], $member->position);

                return preg_match('/رییس|رئیس|president|chair/iu', $position) === 1 && preg_match('/نایب|نائب|vice|deputy/iu', $position) !== 1;
            });

            return [
                'president' => $president ? $present->one($president) : null,
                'slides' => $queries->for('slides')->orderBy('sort_order')->limit(10)->get()->map(fn (Model $m) => $present->one($m, false, false))->all(),
                'featured_posts' => $queries->for('posts')->where('is_featured', true)->latest('published_at')->limit(6)->get()->map(fn (Model $p) => $present->one($p, false, false))->all(),
                'sections' => $sections,
                'events' => $queries->for('events')->where('ends_at', '>', now())->orderBy('starts_at')->limit(4)->get()->map(fn (Model $m) => $present->one($m, false, false))->all(),
                'albums' => $queries->for('albums')->latest('published_at')->limit(3)->get()->map(fn (Model $m) => $present->one($m, false, false))->all(),
                'systems' => $queries->for('links')->where('group', 'systems')->orderBy('sort_order')->get()->map(fn (Model $m) => $present->one($m))->all(),
                'former_presidents' => $queries->for('board-members')->where('role', 'former_president')->orderBy('sort_order')->limit(20)->get()->map(fn (Model $m) => $present->one($m))->all(),
                'board_members' => $queries->for('board-members')->where('role', '!=', 'former_president')->orderBy('sort_order')->limit(20)->get()->map(fn (Model $m) => $present->one($m))->all(),
            ];
        });

        return ApiResponse::success($data);
    }

    public function settings(ContentPresenter $present): JsonResponse
    {
        $data = Cache::remember('site.settings.'.TranslationService::locale(), 60, fn () => Setting::whereIn('key', array_keys(config('site-settings')))->get()->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])->all());

        if (TranslationService::locale() === 'en') {
            foreach (['site_name', 'site_description', 'site_address', 'office_hours', 'announcement_ticker', 'copyright'] as $key) {
                if (! empty($data[$key.'_en'])) {
                    $data[$key] = $data[$key.'_en'];
                }
            }
            $data['site_name'] = $data['site_name_en'] ?? 'Mazandaran Official Justice Experts Council';
            $data['announcement_ticker'] = $data['announcement_ticker_en'] ?? 'Investment for production';
        }

        return ApiResponse::success($data);
    }

    public function navigation(ContentPresenter $present): JsonResponse
    {
        $data = Cache::remember('site.navigation.'.TranslationService::locale(), 60, function () use ($present): array {
            $items = MenuItem::with(['localizedContent', 'page'])->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
                ->filter(fn (MenuItem $item): bool => $item->page_id === null || ($item->page?->isPublic() ?? false));
            $tree = function (?int $parent, string $location, int $depth = 0) use (&$tree, $items, $present): array {
                if ($depth >= 4) {
                    return [];
                }

                return $items->filter(fn (MenuItem $m) => $m->parent_id === $parent && $m->location === $location)
                    ->map(fn (MenuItem $m) => $present->one($m) + ['children' => $tree($m->id, $location, $depth + 1)])->values()->all();
            };
            $result = [];
            foreach (['header', 'footer', 'mobile', 'quick'] as $location) {
                $result[$location] = $tree(null, $location);
            }

            return $result;
        });

        return ApiResponse::success($data);
    }

    public function search(SearchRequest $request, PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $filters = $request->validated();
        $types = isset($filters['type']) ? [$filters['type']] : ['news', 'announcement', 'resolution'];
        $results = [];
        foreach ($types as $type) {
            $page = $queries->filter($queries->for('posts'), 'posts', ['q' => $filters['q'], 'type' => $type])
                ->paginate($filters['per_page'] ?? 5, ['*'], 'page', $filters['page'] ?? 1);
            $results[$type] = [
                'total' => $page->total(),
                'items' => $page->getCollection()->map(fn (Model $m) => $present->one($m, false, false))->all(),
                'pagination' => [
                    'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                    'total' => $page->total(), 'last_page' => $page->lastPage(),
                ],
                'archive_path' => '/news?'.http_build_query(['type' => $type, 'q' => $filters['q']]),
            ];
        }

        return ApiResponse::success(['query' => $filters['q'], 'total' => array_sum(array_column($results, 'total')), 'results' => $results]);
    }

    public function resolveRedirect(Request $request): JsonResponse
    {
        $data = $request->validate(['path' => ['required', 'string', 'max:250', 'regex:/^\/(?!\/)/']]);
        $redirect = LegacyRedirect::where('old_path', $data['path'])->where('is_active', true)->firstOrFail();

        return ApiResponse::success($redirect->only(['old_path', 'new_path', 'status_code']));
    }

    public function sitemap(PublicQuery $queries): JsonResponse
    {
        $entries = [];
        foreach (['posts', 'pages', 'documents', 'experts', 'events', 'agreements', 'albums'] as $resource) {
            $queries->for($resource)->orderBy('id')->chunkById(500, function ($items) use (&$entries, $resource): void {
                foreach ($items as $item) {
                    $segment = $resource === 'posts' ? ($item->type === 'article' ? 'articles' : 'news') : $resource;
                    $entries[] = ['path' => $item instanceof Page ? $item->publicPath() : '/'.$segment.'/'.rawurlencode((string) ($item->slug ?? $item->id)), 'updated_at' => $item->updated_at->toIso8601String()];
                }
            });
        }

        return ApiResponse::success($entries);
    }
}
