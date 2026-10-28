<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\ContentPresenter;
use App\Support\ContentCatalog;
use App\Support\Portal as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ManagementController
{
    public function dashboard(Request $request)
    {
        $data = app()->call([app(DashboardController::class), 'show'])->getData(true)['data'];

        return view('admin.dashboard', compact('data'));
    }

    public function resource(Request $request, string $resource, ?string $action = null)
    {
        abort_unless(P::can($resource), 403);
        if ($resource === 'slides') {
            return redirect('/admin/posts');
        }
        if (in_array($resource, ['media', 'settings', 'security'], true)) {
            return view('admin.'.$resource);
        }
        $config = P::data('admin-resources')[$resource] ?? null;
        abort_unless($config, 404);
        if ($action === null) {
            return view('admin.index', compact('resource', 'config'));
        }
        $initial = null;
        if ($action !== 'new') {
            abort_unless(ctype_digit($action), 404);
            $model = match ($resource) {
                'posts' => Post::query(),'users' => User::query(),'contact-messages' => ContactMessage::query(),'audit-logs' => AuditLog::query(),default => ContentCatalog::model($resource)->newQuery()
            };
            $record = $model->findOrFail((int) $action);
            if ($resource === 'posts') {
                $this->authorizePost($record);
            }
            $initial = app(ContentPresenter::class)->one($record, true);
        }
        abort_if(($action === 'new' && ($config['noCreate'] ?? false)) || ($config['readOnly'] ?? false), 403);

        return view($resource === 'pages' ? 'admin.page-builder' : 'admin.form', compact('resource', 'config', 'initial'));
    }

    private function authorizePost(Post $post): void
    {
        Gate::authorize('update', $post);
    }

    public function preview(int $id)
    {
        abort_unless(P::can('pages'), 403);
        $item = app(ContentPresenter::class)->one(Page::findOrFail($id), true);
        return view('admin.page-preview', compact('item'));
    }
}
