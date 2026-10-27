<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table): void {
            $table->id();
            $table->string('resource', 80);
            $table->unsignedBigInteger('record_id');
            $table->string('locale', 5);
            $table->json('fields');
            $table->longText('search_text')->nullable();
            $table->timestamps();
            $table->unique(['resource', 'record_id', 'locale']);
        });
        Schema::table('album_items', function (Blueprint $table): void {
            $table->string('caption_en', 250)->nullable();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('album_items', function (Blueprint $table): void {
            $table->dropColumn('caption_en');
        });
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('content_translations');
    }
};
