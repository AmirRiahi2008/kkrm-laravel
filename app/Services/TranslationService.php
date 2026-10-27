<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContentTranslation;
use App\Models\SiteModel;
use App\Support\ContentCatalog;
use App\Support\SearchText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

final class TranslationService
{
    public const TEXT_FIELDS = ['title', 'name', 'question', 'answer', 'body', 'biography', 'description', 'summary', 'excerpt', 'first_name', 'last_name', 'city', 'geographical_scope', 'position', 'contact_label', 'partner', 'location', 'bank_name', 'holder', 'purpose', 'meta_title', 'meta_description'];

    public const HTML_FIELDS = ['body', 'answer', 'biography'];

    public static function locale(): string
    {
        return request()->query('locale', request()->header('X-Site-Locale', 'fa')) === 'en' ? 'en' : 'fa';
    }

    public static function rules(Model $model): array
    {
        $fields = array_intersect(self::TEXT_FIELDS, $model->getFillable());
        $rules = ['translations' => ['sometimes', 'array:en'], 'translations.en' => ['sometimes', 'array:'.implode(',', $fields)]];
        foreach ($fields as $field) {
            $limit = in_array($field, self::HTML_FIELDS, true) ? 100000 : (in_array($field, ['description', 'summary', 'excerpt', 'meta_description'], true) ? 1000 : 250);
            $rules['translations.en.'.$field] = ['sometimes', 'nullable', 'string', 'max:'.$limit];
        }

        return $rules;
    }

    public function save(Model $model, ?array $translations): void
    {
        if ($translations === null || ! array_key_exists('en', $translations)) {
            return;
        }
        $resource = ContentCatalog::resource($model);
        $record = ContentTranslation::firstOrNew(['resource' => $resource, 'record_id' => $model->getKey(), 'locale' => 'en']);
        $fields = array_merge($record->fields ?? [], Arr::only($translations['en'], array_intersect(self::TEXT_FIELDS, $model->getFillable())));
        foreach ($fields as $key => $value) {
            $fields[$key] = in_array($key, self::HTML_FIELDS, true) ? app(HtmlService::class)->clean($value) : ($value === null ? null : trim($value));
        }
        $record->fields = $fields;
        $record->search_text = SearchText::normalize(strip_tags(implode(' ', array_filter($fields, 'is_string'))));
        $record->save();
        $model->unsetRelation('localizedContent');
        foreach (['site.home.en', 'site.navigation.en', 'site.settings.en'] as $key) {
            Cache::forget($key);
        }
    }

    public function present(Model $model, array $data, bool $admin): array
    {
        if (! $model instanceof SiteModel) {
            return $data;
        }
        $fields = $model->localizedContent->firstWhere('locale', 'en')?->fields ?? [];
        if ($admin) {
            $data['translations'] = ['en' => $fields];

            return $data;
        }
        $locale = self::locale();
        $data['content_locale'] = 'fa';
        if ($locale === 'en') {
            foreach ($fields as $key => $value) {
                if (array_key_exists($key, $data) && $this->hasContent($value)) {
                    $data[$key] = $value;
                }
            }
            $titleField = collect(['title', 'name', 'question', 'first_name'])->first(fn ($key) => array_key_exists($key, $data));
            if ($titleField && $this->hasContent($fields[$titleField] ?? null)) {
                $data['content_locale'] = 'en';
            }
            $bodyField = collect([...self::HTML_FIELDS, 'description'])->first(fn ($key) => $this->hasContent($data[$key] ?? null));
            $data['body_locale'] = $bodyField && $this->hasContent($fields[$bodyField] ?? null) ? 'en' : 'fa';
        }

        return $data;
    }

    private function hasContent(mixed $value): bool
    {
        return is_string($value) && (trim(strip_tags($value)) !== '' || preg_match('/<img\b[^>]*\bsrc\s*=/i', $value) === 1);
    }
}
