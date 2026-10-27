<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('board_members', 'contact_label')) {
            Schema::table('board_members', function (Blueprint $table): void {
                $table->string('contact_label', 100)->nullable();
            });
        }

        if (! Schema::hasColumn('board_members', 'contact_url')) {
            Schema::table('board_members', function (Blueprint $table): void {
                $table->text('contact_url')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['contact_label', 'contact_url'] as $column) {
            if (Schema::hasColumn('board_members', $column)) {
                Schema::table('board_members', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
