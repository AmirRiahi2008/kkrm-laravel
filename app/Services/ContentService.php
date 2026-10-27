<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Album;
use App\Models\Category;
use App\Models\Expert;
use App\Models\LegacyRedirect;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Qualification;
use App\Models\Tariff;
use App\Models\User;
use App\Support\PagePath;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ContentService
{
    public function save(Model $model, array $data, User $actor): Model
    {
        return DB::transaction(function () use ($model, $data, $actor): Model {
            $candidate = array_merge($model->getAttributes(), $data);
            if ($model instanceof Page) {
                if (! empty($candidate['path']) && (! PagePath::valid($candidate['path']) || LegacyRedirect::where('old_path', $candidate['path'])->where('is_active', true)->exists())) {
                    throw ValidationException::withMessages(['path' => 'این نشانی رزرو شده یا برای تغییر مسیر استفاده شده است. نشانی دیگری انتخاب کنید.']);
                }
                if (! $model->exists && empty($data['slug']) && ! empty($data['path'])) {
                    $base = mb_substr(str_replace('/', '-', trim($data['path'], '/')), 0, 170);
                    $slug = rtrim($base, '-');
                    while (Page::withTrashed()->where('slug', $slug)->exists()) {
                        $slug = rtrim($base, '-').'-'.Str::lower(Str::random(8));
                    }
                    $data['slug'] = $slug;
                }
            }
            if ($model instanceof MenuItem && ! empty($candidate['page_id'])) {
                $page = Page::find($candidate['page_id']);
                if (! $page) {
                    throw ValidationException::withMessages(['page_id' => 'صفحه انتخاب‌شده موجود نیست.']);
                }
                $data['url'] = $page->publicPath();
                $candidate['url'] = $data['url'];
            }
            if ($model instanceof MenuItem) {
                $this->validateMenuPlacement($model, $candidate);
            }
            $this->validateDates($candidate);
            foreach (['cover_id', 'image_id', 'photo_id', 'file_id'] as $key) {
                if (! empty($data[$key])) {
                    $this->validateMedia((int) $data[$key], $actor, $key === 'file_id' ? null : 'image/');
                }
            }
            if ($model instanceof Category || $model instanceof MenuItem) {
                $this->validateParent($model, $candidate);
            }
            if ($model instanceof Album && $model->exists && isset($data['type']) && $data['type'] !== $model->type) {
                $prefix = 'image/';
                $invalidItems = Media::whereIn('id', $model->items()->select('media_id'))->where('mime_type', 'not like', $prefix.'%')->exists();
                if ($invalidItems) {
                    throw ValidationException::withMessages(['type' => 'نوع آلبوم با فایل‌های موجود هماهنگ نیست.']);
                }
            }
            if ($model instanceof LegacyRedirect) {
                $path = $candidate['new_path'];
                $seen = [$candidate['old_path']];
                for ($i = 0; $i < 10; $i++) {
                    if (in_array($path, $seen, true)) {
                        throw ValidationException::withMessages(['new_path' => 'زنجیره تغییر مسیر نباید حلقه داشته باشد.']);
                    }
                    $seen[] = $path;
                    $next = LegacyRedirect::where('old_path', $path)->where('is_active', true)->first();
                    if (! $next) {
                        break;
                    }
                    $path = $next->new_path;
                    if ($i === 9) {
                        throw ValidationException::withMessages(['new_path' => 'زنجیره تغییر مسیر بیش از حد طولانی است.']);
                    }
                }
            }
            if ($model instanceof Expert && array_key_exists('qualification_ids', $data)) {
                $count = Qualification::where('discipline_id', $candidate['discipline_id'])->whereIn('id', $data['qualification_ids'])->count();
                if ($count !== count($data['qualification_ids'])) {
                    throw ValidationException::withMessages(['qualification_ids' => 'صلاحیت‌ها باید متعلق به رشته کارشناس باشند.']);
                }
            } elseif ($model instanceof Expert && $model->exists && $model->discipline_id !== ($candidate['discipline_id'] ?? null) && $model->qualifications()->exists()) {
                throw ValidationException::withMessages(['qualification_ids' => 'با تغییر رشته، صلاحیت‌ها را هم مشخص کنید.']);
            }
            if ($model instanceof Tariff) {
                $rules = $data['rules'] ?? $model->rules;
                app(TariffService::class)->validateRules($rules);
                if (! ($candidate['is_demo'] ?? true) && ($actor->role !== UserRole::SuperAdmin || empty($candidate['source_url']) || empty($candidate['approval_reference']))) {
                    throw ValidationException::withMessages(['approval_reference' => 'تعرفه واقعی نیازمند تأیید مدیر ارشد و مرجع معتبر است.']);
                }
            }
            foreach (['body', 'answer', 'biography'] as $key) {
                if (array_key_exists($key, $data)) {
                    $data[$key] = app(HtmlService::class)->clean($data[$key]);
                    if ($model instanceof Page && $key === 'body' && preg_replace('/[\s\p{Z}]+/u', '', html_entity_decode(strip_tags($data[$key] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '' && preg_match('/<img\b[^>]*\bsrc\s*=/i', $data[$key] ?? '') !== 1) {
                        throw ValidationException::withMessages(['body' => 'محتوای صفحه نمی‌تواند خالی باشد.']);
                    }
                }
            }
            if (($candidate['status'] ?? null) === 'published' && empty($candidate['published_at']) && in_array('published_at', $model->getFillable(), true)) {
                $data['published_at'] = now();
            }
            $model->fill(Arr::except($data, ['qualification_ids', 'translations']))->save();
            app(TranslationService::class)->save($model, $data['translations'] ?? null);
            if ($model instanceof Expert && array_key_exists('qualification_ids', $data)) {
                $model->qualifications()->sync($data['qualification_ids']);
            }

            return $model->refresh();
        });
    }

    public function validateMedia(int $id, User $actor, ?string $prefix = null): Media
    {
        $media = Media::findOrFail($id);
        if (str_starts_with($media->mime_type, 'video/')) {
            throw ValidationException::withMessages([
                'media' => 'استفاده از فایل ویدئویی مجاز نیست.',
            ]);
        }
        abort_unless($actor->role->managesContent() || $media->uploaded_by === $actor->id, 403);
        if ($prefix !== null && ! str_starts_with($media->mime_type, $prefix)) {
            throw ValidationException::withMessages(['media' => 'نوع فایل برای این بخش مناسب نیست.']);
        }
        if (config('kkrm.clamav_enabled') && $media->scan_status !== 'clean') {
            throw ValidationException::withMessages(['media' => 'فایل هنوز تأیید امنیتی نشده است.']);
        }

        return $media;
    }

    public function validateDates(array $data): void
    {
        foreach ([['starts_at', 'ends_at'], ['started_at', 'ended_at'], ['effective_from', 'effective_to'], ['published_at', 'expires_at']] as [$from, $to]) {
            if (! empty($data[$from]) && ! empty($data[$to]) && CarbonImmutable::parse($data[$to])->lessThanOrEqualTo(CarbonImmutable::parse($data[$from]))) {
                throw ValidationException::withMessages([$to => 'تاریخ پایان باید بعد از تاریخ آغاز باشد.']);
            }
        }
    }

    private function validateParent(Model $model, array $candidate): void
    {
        $nodes = $model->newQuery()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $parentId = $candidate['parent_id'] ?? null;
        $seen = $model->exists ? [$model->id] : [];
        $depth = 0;
        while ($parentId !== null) {
            if (in_array((int) $parentId, $seen, true) || ++$depth > 3) {
                throw ValidationException::withMessages(['parent_id' => 'ساختار درختی نامعتبر است یا بیش از چهار سطح دارد.']);
            }
            $seen[] = (int) $parentId;
            $parent = $nodes->get($parentId);
            abort_unless($parent, 404);
            if ($model instanceof MenuItem && $parent->location !== ($candidate['location'] ?? null)) {
                throw ValidationException::withMessages(['parent_id' => 'والد باید در همان منو باشد.']);
            }
            $parentId = $parent->parent_id;
        }
        if (! $model->exists) {
            return;
        }
        $frontier = [$model->id];
        $visited = [$model->id];
        while ($frontier !== []) {
            $children = $nodes->filter(fn (Model $node) => in_array($node->parent_id, $frontier, true));
            if ($children->isEmpty()) {
                break;
            }
            if (++$depth > 3 || $children->contains(fn (Model $node) => in_array($node->id, $visited, true))) {
                throw ValidationException::withMessages(['parent_id' => 'عمق شاخه‌های زیرمجموعه بیش از چهار سطح می‌شود.']);
            }
            if ($model instanceof MenuItem && $children->contains(fn (Model $node) => $node->location !== $candidate['location'])) {
                throw ValidationException::withMessages(['location' => 'محل منوی دارای زیرمجموعه را نمی‌توان به تنهایی تغییر داد.']);
            }
            $frontier = $children->keys()->all();
            $visited = array_merge($visited, $frontier);
        }
    }

    private function validateMenuPlacement(MenuItem $model, array $candidate): void
    {
        $mode = $candidate['display_mode'] ?? 'mega';
        if ($mode !== 'mega' && ($candidate['location'] ?? null) !== 'header') {
            throw ValidationException::withMessages(['display_mode' => 'نمایش در ناوبری اصلی فقط برای بالای سایت قابل انتخاب است.']);
        }
        if (in_array($mode, ['main', 'both'], true)) {
            if (! empty($candidate['parent_id'])) {
                throw ValidationException::withMessages(['parent_id' => 'لینک ناوبری اصلی باید مستقل باشد؛ گزینه زیر یک لینک دیگر را خالی کنید.']);
            }
            if (empty($candidate['url'])) {
                throw ValidationException::withMessages(['url' => 'برای نمایش مستقیم در ناوبری اصلی، مسیر مقصد یا یک صفحه را انتخاب کنید.']);
            }
            if ($mode === 'main' && $model->exists && $model->children()->exists()) {
                throw ValidationException::withMessages(['display_mode' => 'این لینک زیرمجموعه دارد. برای حفظ زیرمجموعه‌ها، گزینه ناوبری اصلی و مگامنو را انتخاب کنید.']);
            }
        }
        if (! empty($candidate['parent_id'])) {
            $parent = MenuItem::find($candidate['parent_id']);
            if ($parent?->display_mode === 'main') {
                throw ValidationException::withMessages(['parent_id' => 'این لینک فقط در ناوبری اصلی نمایش داده می‌شود و نمی‌تواند بخش میزبان زیرمنو باشد.']);
            }
        }
    }
}
