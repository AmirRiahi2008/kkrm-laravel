<?php

declare(strict_types=1);

namespace App\Support;

use App\Models;
use Illuminate\Database\Eloquent\Model;

final class ContentCatalog
{
    public const MODELS = [
        'categories' => Models\Category::class,
        'tags' => Models\Tag::class,
        'pages' => Models\Page::class,
        'documents' => Models\Document::class,
        'expert-groups' => Models\ExpertGroup::class,
        'disciplines' => Models\Discipline::class,
        'qualifications' => Models\Qualification::class,
        'experts' => Models\Expert::class,
        'board-terms' => Models\BoardTerm::class,
        'board-members' => Models\BoardMember::class,
        'agreements' => Models\Agreement::class,
        'albums' => Models\Album::class,
        'events' => Models\Event::class,
        'faqs' => Models\Faq::class,
        'slides' => Models\Slide::class,
        'links' => Models\ExternalLink::class,
        'menu-items' => Models\MenuItem::class,
        'bank-accounts' => Models\BankAccount::class,
        'tariffs' => Models\Tariff::class,
        'redirects' => Models\LegacyRedirect::class,

    ];

    public const PUBLIC_RESOURCES = ['categories', 'tags', 'pages', 'documents', 'expert-groups', 'disciplines', 'qualifications', 'experts', 'board-terms', 'board-members', 'agreements', 'albums', 'events', 'faqs', 'slides', 'links', 'bank-accounts', 'tariffs'];

    public const PUBLISHED = ['posts', 'pages', 'documents', 'agreements', 'albums', 'events'];

    public const PUBLIC_FIELDS = [
        Models\Category::class => ['name', 'slug', 'parent_id', 'is_active', 'sort_order'],
        Models\Tag::class => ['name', 'slug'],
        Models\Page::class => ['title', 'slug', 'path', 'summary', 'body', 'cover_id', 'status', 'published_at', 'meta_title', 'meta_description'],
        Models\Document::class => ['title', 'slug', 'type', 'description', 'body', 'file_id', 'source_url', 'document_number', 'issued_at', 'is_demo', 'status', 'published_at', 'meta_title', 'meta_description'],
        Models\ExpertGroup::class => ['name', 'slug', 'code', 'description', 'is_active', 'sort_order'],
        Models\Discipline::class => ['expert_group_id', 'name', 'code', 'is_active', 'sort_order'],
        Models\Qualification::class => ['discipline_id', 'title', 'code', 'description', 'is_active', 'sort_order'],
        Models\Expert::class => ['discipline_id', 'photo_id', 'first_name', 'last_name', 'license_number', 'city', 'geographical_scope', 'show_contact', 'is_published', 'status', 'biography'],
        Models\BoardTerm::class => ['title', 'started_at', 'ended_at', 'is_active', 'sort_order'],
        Models\BoardMember::class => ['board_term_id', 'photo_id', 'name', 'position', 'role', 'biography', 'contact_label', 'contact_url', 'is_active', 'sort_order'],
        Models\Agreement::class => ['title', 'slug', 'partner', 'body', 'file_id', 'cover_id', 'starts_at', 'ends_at', 'is_demo', 'status', 'published_at'],
        Models\Album::class => ['title', 'slug', 'description', 'cover_id', 'type', 'event_date', 'status', 'published_at'],
        Models\Event::class => ['title', 'slug', 'body', 'cover_id', 'mode', 'location', 'starts_at', 'ends_at', 'registration_url', 'status', 'published_at', 'meta_title', 'meta_description'],
        Models\Faq::class => ['question', 'answer', 'group', 'is_active', 'sort_order'],
        Models\Slide::class => ['title', 'image_id', 'target_url', 'starts_at', 'ends_at', 'is_active', 'sort_order'],
        Models\ExternalLink::class => ['title', 'code', 'url', 'group', 'icon', 'is_active', 'sort_order'],
        Models\MenuItem::class => ['location', 'display_mode', 'title', 'url', 'page_id', 'parent_id', 'open_new_tab', 'is_active', 'sort_order'],
        Models\BankAccount::class => ['bank_name', 'holder', 'account_number', 'iban', 'card_number', 'purpose', 'is_demo', 'is_active', 'sort_order'],
        Models\Tariff::class => ['name', 'slug', 'currency', 'effective_from', 'effective_to', 'source_url', 'approval_reference', 'rules', 'is_demo', 'is_active', 'sort_order'],
        Models\LegacyRedirect::class => ['old_path', 'new_path', 'status_code', 'is_active', 'sort_order'],

    ];

    public static function model(string $resource): Model
    {
        abort_unless(isset(self::MODELS[$resource]), 404);

        return new (self::MODELS[$resource]);
    }

    public static function resource(Model $model): string
    {
        return array_search($model::class, self::MODELS, true) ?: 'posts';
    }
}
