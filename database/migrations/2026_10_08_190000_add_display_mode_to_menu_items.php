<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('menu_items', 'display_mode')) {
            Schema::table('menu_items', function (Blueprint $table): void {
                $table->string('display_mode', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('menu_items', 'display_mode')) {
            Schema::table('menu_items', function (Blueprint $table): void {
                $table->dropColumn('display_mode');
            });
        }
    }
};
