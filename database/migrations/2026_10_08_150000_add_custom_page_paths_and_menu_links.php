<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('path', 240)->nullable()->unique();
            $table->string('summary', 500)->nullable();
        });

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('page_id');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropUnique(['path']);
            $table->dropColumn(['path', 'summary']);
        });
    }
};
