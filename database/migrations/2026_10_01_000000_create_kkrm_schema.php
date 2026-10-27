<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('editor')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('session_version')->default(1);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('sha256');
            $table->string('alt')->nullable();
            $table->string('scan_status')->default('unchecked');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->index(['type', 'status', 'published_at']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->index();
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('file_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('source_url')->nullable();
            $table->string('document_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->boolean('is_demo')->default(true);
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expert_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('disciplines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expert_group_id')->constrained('expert_groups')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('qualifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discipline_id')->constrained('disciplines')->restrictOnDelete();
            $table->string('title');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('experts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discipline_id')->constrained('disciplines')->restrictOnDelete();
            $table->foreignId('photo_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('license_number')->unique();
            $table->string('city')->index();
            $table->string('geographical_scope');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('show_contact')->default(false);
            $table->boolean('is_published')->default(false);
            $table->string('status')->default('active')->index();
            $table->text('biography')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('board_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('board_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_term_id')->nullable()->constrained('board_terms')->nullOnDelete();
            $table->foreignId('photo_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('name');
            $table->string('position');
            $table->string('role');
            $table->text('biography')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agreements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('partner');
            $table->longText('body');
            $table->foreignId('file_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_demo')->default(true);
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('albums', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('type')->default('photo');
            $table->date('event_date')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('mode');
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->text('registration_url')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('group')->default('general');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('slides', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->foreignId('image_id')->constrained('media')->restrictOnDelete();
            $table->text('target_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('external_links', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('code')->unique();
            $table->text('url');
            $table->string('group')->index();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->string('location')->index();
            $table->string('title');
            $table->text('url')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->boolean('open_new_tab')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('bank_name');
            $table->string('holder');
            $table->string('account_number');
            $table->string('iban')->nullable();
            $table->string('card_number')->nullable();
            $table->string('purpose');
            $table->boolean('is_demo')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tariffs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('currency')->default('IRR');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('source_url')->nullable();
            $table->string('approval_reference')->nullable();
            $table->json('rules');
            $table->boolean('is_demo')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('legacy_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('old_path')->unique();
            $table->string('new_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('post_tag', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });
        Schema::create('post_media', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->primary(['post_id', 'media_id']);
        });
        Schema::create('expert_qualification', function (Blueprint $table): void {
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qualification_id')->constrained()->restrictOnDelete();
            $table->primary(['expert_id', 'qualification_id']);
        });
        Schema::create('album_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['album_id', 'media_id']);
        });
        Schema::create('post_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot');
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->text('internal_note')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('post_revisions');
        Schema::dropIfExists('album_items');
        Schema::dropIfExists('expert_qualification');
        Schema::dropIfExists('post_media');
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('legacy_redirects');
        Schema::dropIfExists('tariffs');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('external_links');
        Schema::dropIfExists('slides');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('events');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('agreements');
        Schema::dropIfExists('board_members');
        Schema::dropIfExists('board_terms');
        Schema::dropIfExists('experts');
        Schema::dropIfExists('qualifications');
        Schema::dropIfExists('disciplines');
        Schema::dropIfExists('expert_groups');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('media');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'is_active', 'session_version', 'last_login_at']);
        });
    }
};
