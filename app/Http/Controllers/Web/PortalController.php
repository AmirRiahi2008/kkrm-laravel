<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\ExpertDirectoryController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SlideshowController;
use App\Http\Requests\IndexRequest;
use App\Models\LegacyRedirect;
use App\Services\ContentPresenter;
use App\Services\PublicQuery;
use App\Support\Portal as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

final class PortalController
{
    public function __construct(private PublicQuery $queries, private ContentPresenter $present) {}

    private function shell(): void
    {
        $site = app(SiteController::class);
        View::share('settings', $site->settings($this->present)->getData(true)['data']);
        View::share('navigation', $site->navigation($this->present)->getData(true)['data']);
    }

    public function home(Request $request)
    {
        if ($request->filled('page_id')) {
            $redirect = LegacyRedirect::where('old_path', '/?page_id='.$request->query('page_id'))->where('is_active', true)->first();
            if ($redirect && P::href($redirect->new_path) && str_starts_with($redirect->new_path, '/')) {
                return redirect(P::url($redirect->new_path), $redirect->status_code);
            }
        }
        $this->shell();
        $data = app(SiteController::class)->home($this->queries, $this->present)->getData(true)['data'];
        $slides = app()->call([app(SlideshowController::class), '__invoke'])->getData(true)['data'];

        return view('site.home', compact('data', 'slides'));
    }

    public function page(Request $request, ?string $path = null)
    {
        $this->shell();
        $path = preg_replace('#^en(?:/|$)#', '', $request->decodedPath());
        $segments = explode('/', trim($path, '/'));
        $resource = $segments[0];
        $key = $segments[1] ?? null;
        if ($path === 'contact') {
            return view('site.contact');
        }
        if (in_array($path, ['download', 'pwa'], true)) {
            return view('site.download');
        }
        if ($path === 'search') {
            $query = trim((string) $request->query('q', ''));
            $sections = [];
            if (mb_strlen($query) >= 2) {
                foreach (['news' => 'اخبار کانون', 'announcement' => 'اطلاعیه‌ها', 'resolution' => 'مصوبات'] as $type => $label) {
                    $results = $this->queries->filter($this->queries->for('posts')->where('type', $type), 'posts', ['q' => $query]);
                    $sections[] = ['type' => $type, 'label' => $label, 'total' => (clone $results)->count(), 'items' => $results->limit(6)->get()];
                }
            }

            return view('site.search', compact('sections', 'query'));
        }
        if (in_array($path, ['fees', 'tariffs', 'tariffs/calculator'], true)) {
            return view('site.fees', ['tariffs' => $this->queries->for('tariffs')->orderByDesc('effective_from')->get()]);
        }
        if ($path === 'pages/history') {
            return view('site.history');
        }
        $config = P::data('public-resources')[$resource] ?? null;
        if (! $config) {
            $page = $this->queries->for('pages')->where('path', '/'.$path)->firstOrFail();

            return view('site.detail', ['item' => $this->present->one($page), 'resource' => 'pages']);
        }
        abort_if(count($segments) > 2, 404);
        $api = $config['api'];
        if ($key) {
            $query = $this->queries->for($api);
            $slug = in_array('slug', $query->getModel()->getFillable(), true);
            $model = $slug ? (clone $query)->where('slug', $key)->first() : null;
            if (! $model && ctype_digit($key)) {
                $model = $query->find($key);
            }
            if (! $model && $resource === 'documents') {
                $fallback = ['qualification-titles' => '/qualifications', 'tariff-description' => '/documents?type=tariff', 'increase-qualification' => '/documents?q=صلاحیت'][$key] ?? null;
                if ($fallback) {
                    return redirect(P::url($fallback));
                }
            }
            abort_unless($model, 404);
            $item = $this->present->one($model);
            if ($resource === 'news' && ($item['type'] ?? '') === 'article') {
                return redirect(P::path('articles', $item));
            }
            if ($resource === 'articles' && ($item['type'] ?? '') !== 'article') {
                return redirect(P::path('news', $item));
            }
            if ($resource === 'pages' && ! empty($item['path'])) {
                return redirect(P::path('pages', $item));
            }
            $related = [];
            $relatedResource = '';
            $relatedLabel = '';
            $relatedQuery = '';
            $relations = ['expert-groups' => ['disciplines', 'expert_group_id', 'رشته‌های این گروه'], 'disciplines' => ['qualifications', 'discipline_id', 'صلاحیت‌های این رشته'], 'board-terms' => ['board-members', 'board_term_id', 'اعضای این دوره']];
            if (isset($relations[$resource])) {
                [$relatedResource,$field,$relatedLabel] = $relations[$resource];
                $related = $this->queries->for($relatedResource)->where($field, $model->id)->get()->map(fn ($m) => $this->present->one($m))->all();
                $relatedQuery = $field.'='.$model->id;
            }
            if (in_array($resource, ['categories', 'tags'], true)) {
                $relatedResource = 'news';
                $relatedLabel = 'مطالب مرتبط';
                $filter = $resource === 'tags' ? 'tag' : 'category';
                $relatedQuery = $filter.'='.rawurlencode($model->slug);
                $related = $this->queries->filter($this->queries->for('posts'), 'posts', [$filter => $model->slug])
                    ->limit(50)->get()->map(fn ($entry) => $this->present->one($entry))->all();
            }

            return view('site.detail', compact('item', 'resource', 'related', 'relatedResource', 'relatedLabel', 'relatedQuery'));
        }
        $filters = $request->validate(IndexRequest::createFrom($request)->rules());
        if ($resource === 'articles') {
            $filters['type'] = 'article';
        }
        if ($resource === 'systems') {
            $filters['group'] = 'systems';
        }
        $items = $this->queries->filter($this->queries->for($api), $api, $filters)->paginate(12)->withQueryString();
        $directory = [];
        if ($resource === 'experts') {
            $directory = app()->call([app(ExpertDirectoryController::class), 'filters'])->getData(true)['data'];
        }
        $filterOptions = [];
        if ($resource === 'board-members') {
            $filterOptions['terms'] = $this->queries->for('board-terms')->get();
        }
        if ($resource === 'qualifications') {
            $filterOptions['disciplines'] = $this->queries->for('disciplines')->get();
        }

        return view('site.archive', ['resource' => $resource, 'label' => P::t($config['label']), 'items' => $items, 'directory' => $directory, 'filterOptions' => $filterOptions]);
    }
}
