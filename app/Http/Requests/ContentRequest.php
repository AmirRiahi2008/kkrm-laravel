<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\DatabaseDate;
use App\Rules\SafePagePath;
use App\Rules\SafeUrl;
use App\Services\TranslationService;
use App\Support\ContentCatalog;
use App\Support\ContentTypes;
use App\Support\PagePath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ContentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->route('resource') === 'pages' && is_string($this->input('path'))) {
            $this->merge(['path' => PagePath::normalize($this->input('path'))]);
        }
    }

    public function authorize(): bool
    {
        return Gate::allows('manage-content');
    }

    public function rules(): array
    {
        $resource = (string) $this->route('resource');
        $table = ContentCatalog::model($resource)->getTable();
        $id = $this->route('item');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $text = [$required, 'string', 'max:250'];
        $optionalText = ['sometimes', 'nullable', 'string', 'max:1000'];
        $slug = [$required, 'string', 'max:180', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique($table, 'slug')->ignore($id)];
        $code = [$required, 'string', 'max:100', Rule::unique($table, 'code')->ignore($id)];
        $body = [$required, 'string', 'max:100000'];
        $foreign = fn (string $t, bool $must = false) => [$must ? $required : 'sometimes', $must ? 'required' : 'nullable', 'integer', Rule::exists($t, 'id')->whereNull('deleted_at')];
        $url = ['sometimes', 'nullable', 'string', 'max:2000', new SafeUrl];
        $image = $foreign('media');
        $rules = match ($resource) {
            'categories' => ['name' => $text, 'slug' => $slug, 'parent_id' => $foreign('categories')],
            'tags' => ['name' => $text, 'slug' => $slug],
            'pages' => [
                'title' => $text,
                'slug' => [$this->isMethod('POST') ? 'required_without:path' : 'sometimes', 'string', 'max:180', 'regex:/^[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u', Rule::unique($table, 'slug')->ignore($id)],
                'path' => ['sometimes', 'nullable', 'string', 'max:240', new SafePagePath, Rule::unique($table, 'path')->ignore($id)],
                'summary' => ['sometimes', 'nullable', 'string', 'max:500'],
                'body' => $body, 'cover_id' => $image,
            ],
            'documents' => [
                'title' => $text, 'slug' => $slug, 'type' => [$required, Rule::in(ContentTypes::DOCUMENTS)],
                'description' => $optionalText, 'body' => ['sometimes', 'nullable', 'string', 'max:100000'],
                'file_id' => $image, 'source_url' => $url, 'document_number' => ['sometimes', 'nullable', 'string', 'max:100'],
                'issued_at' => ['sometimes', 'nullable', 'date'], 'is_demo' => ['sometimes', 'boolean'],
            ],
            'expert-groups' => ['name' => $text, 'slug' => $slug, 'code' => $code, 'description' => $optionalText],
            'disciplines' => ['expert_group_id' => $foreign('expert_groups', true), 'name' => $text, 'code' => $code],
            'qualifications' => ['discipline_id' => $foreign('disciplines', true), 'title' => $text, 'code' => $code, 'description' => $optionalText],
            'experts' => [
                'discipline_id' => $foreign('disciplines', true), 'photo_id' => $image,
                'first_name' => $text, 'last_name' => $text,
                'license_number' => [$required, 'string', 'max:100', Rule::unique('experts', 'license_number')->ignore($id)],
                'city' => $text, 'geographical_scope' => $text, 'biography' => $optionalText,
                'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[0-9 -]{7,20}$/'],
                'email' => ['sometimes', 'nullable', 'email', 'max:200'],
                'show_contact' => ['sometimes', 'boolean'], 'is_published' => ['sometimes', 'boolean'],
                'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
                'qualification_ids' => ['sometimes', 'array', 'max:100'],
                'qualification_ids.*' => ['integer', 'distinct', Rule::exists('qualifications', 'id')->whereNull('deleted_at')],
            ],
            'board-terms' => ['title' => $text, 'started_at' => [$required, 'date'], 'ended_at' => ['sometimes', 'nullable', 'date']],
            'board-members' => [
                'board_term_id' => $foreign('board_terms'), 'name' => $text, 'position' => $text,
                'role' => [$required, Rule::in(['board', 'inspector', 'prosecutor', 'former_president'])],
                'photo_id' => $image, 'biography' => $optionalText,
                'contact_label' => ['sometimes', 'nullable', 'string', 'max:100'],
                'contact_url' => $url,
            ],
            'agreements' => [
                'title' => $text, 'slug' => $slug, 'partner' => $text, 'body' => $body,
                'file_id' => $image, 'cover_id' => $image, 'is_demo' => ['sometimes', 'boolean'],
                'starts_at' => ['sometimes', 'nullable', 'date'], 'ends_at' => ['sometimes', 'nullable', 'date'],
            ],
            'albums' => [
                'title' => $text, 'slug' => $slug, 'description' => $optionalText,
                'cover_id' => $image, 'type' => ['sometimes', Rule::in(['photo'])],
                'event_date' => ['sometimes', 'nullable', 'date'],
            ],
            'events' => [
                'title' => $text, 'slug' => $slug, 'body' => $body, 'cover_id' => $image,
                'mode' => [$required, Rule::in(['in_person', 'online', 'hybrid'])],
                'location' => $optionalText, 'starts_at' => [$required, 'date'],
                'ends_at' => [$required, 'date'], 'registration_url' => $url,
            ],
            'faqs' => ['question' => $text, 'answer' => [$required, 'string', 'max:10000'], 'group' => ['sometimes', 'string', 'max:100']],
            'slides' => [
                'title' => $text, 'image_id' => $foreign('media', true),
                'target_url' => ['sometimes', 'nullable', 'string', 'max:2000', new SafeUrl(true)],
                'starts_at' => ['sometimes', 'nullable', 'date'], 'ends_at' => ['sometimes', 'nullable', 'date'],
            ],
            'links' => [
                'title' => $text, 'code' => $code, 'url' => [$required, 'string', 'max:2000', new SafeUrl],
                'group' => [$required, Rule::in(['systems', 'useful', 'quick', 'social'])],
                'icon' => ['sometimes', 'nullable', 'string', 'max:100'],
            ],
            'menu-items' => [
                'location' => [$required, Rule::in(['header', 'footer', 'mobile', 'quick'])], 'title' => $text,
                'display_mode' => ['sometimes', Rule::in(['main', 'mega', 'both'])],
                'url' => ['sometimes', 'nullable', 'string', 'max:2000', new SafeUrl(true)],
                'page_id' => $foreign('pages'),
                'parent_id' => $foreign('menu_items'), 'open_new_tab' => ['sometimes', 'boolean'],
            ],
            'bank-accounts' => [
                'bank_name' => $text, 'holder' => $text, 'purpose' => $text,
                'account_number' => [$required, 'string', 'regex:/^[0-9-]{3,30}$/'],
                'iban' => ['sometimes', 'nullable', 'string', 'regex:/^IR[0-9]{24}$/'],
                'card_number' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{16}$/'],
                'is_demo' => ['sometimes', 'boolean'],
            ],
            'tariffs' => [
                'name' => $text, 'slug' => $slug, 'currency' => ['sometimes', Rule::in(['IRR'])],
                'effective_from' => [$required, 'date'], 'effective_to' => ['sometimes', 'nullable', 'date'],
                'source_url' => $url, 'approval_reference' => ['sometimes', 'nullable', 'string', 'max:250'],
                'is_demo' => ['sometimes', 'boolean'], 'rules' => [$required, 'array:base_fee,minimum_fee,maximum_fee,tiers,panel_multipliers'],
                'rules.base_fee' => ['required_with:rules', 'integer', 'between:0,1000000000'],
                'rules.minimum_fee' => ['required_with:rules', 'integer', 'between:0,10000000000'],
                'rules.maximum_fee' => ['present_with:rules', 'nullable', 'integer', 'between:1,10000000000'],
                'rules.tiers' => ['required_with:rules', 'array', 'min:1', 'max:20'],
                'rules.tiers.*' => ['array:up_to,rate_bps'],
                'rules.tiers.*.up_to' => ['present', 'nullable', 'integer', 'between:1,1000000000000000'],
                'rules.tiers.*.rate_bps' => ['required', 'numeric', 'between:0,10000', 'decimal:0,3'],
                'rules.panel_multipliers' => ['required_with:rules', 'array:1,2,3,4,5', 'size:5'],
                'rules.panel_multipliers.*' => ['required', 'integer', 'between:10000,50000'],
            ],
            'redirects' => [
                'old_path' => [$required, 'string', 'max:250', 'regex:/^\/(?!\/)/', new SafeUrl(true), Rule::unique('legacy_redirects', 'old_path')->ignore($id)],
                'new_path' => [$required, 'string', 'max:250', 'regex:/^\/(?!\/)/', new SafeUrl(true)],
                'status_code' => ['sometimes', Rule::in([301, 308])],
            ],
            default => [],
        };
        $fields = ContentCatalog::model($resource)->getFillable();
        if (in_array('is_active', $fields, true)) {
            $rules['is_active'] = ['sometimes', 'boolean'];
            $rules['sort_order'] = ['sometimes', 'integer', 'between:0,100000'];
        }
        if (in_array($resource, ContentCatalog::PUBLISHED, true)) {
            $rules += [
                'status' => ['sometimes', Rule::in(ContentTypes::CONTENT_STATUSES)],
                'show_in_slideshow' => ['sometimes', 'boolean'],
                'published_at' => ['sometimes', 'nullable', 'date'],
            ];
        }
        if (in_array('meta_title', $fields, true)) {
            $rules['meta_title'] = ['sometimes', 'nullable', 'string', 'max:250'];
            $rules['meta_description'] = ['sometimes', 'nullable', 'string', 'max:500'];
        }

        foreach ($rules as $field => $constraints) {
            if (in_array('date', $constraints, true)) {
                $rules[$field][] = new DatabaseDate;
            }
        }

        return $rules + TranslationService::rules(ContentCatalog::model($resource));
    }
}
