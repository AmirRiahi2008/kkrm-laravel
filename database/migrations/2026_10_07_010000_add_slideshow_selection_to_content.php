<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['posts', 'pages', 'documents', 'agreements', 'albums', 'events'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->boolean('show_in_slideshow')->default(false);
                $table->index(['show_in_slideshow', 'status', 'published_at'], $name.'_slideshow_visibility_index');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->dropIndex($name.'_slideshow_visibility_index');
                $table->dropColumn('show_in_slideshow');
            });
        }
    }
};
