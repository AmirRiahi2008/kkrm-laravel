<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'posts' => ['published_at', 'expires_at'],
        'pages' => ['published_at'],
        'documents' => ['published_at'],
        'agreements' => ['published_at'],
        'albums' => ['published_at'],
        'events' => ['published_at'],
        'slides' => ['starts_at', 'ends_at'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->dateTime($column)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $name => $columns) {
            foreach ($columns as $column) {
                $unsupported = DB::table($name)->where(function ($query) use ($column): void {
                    $query->where($column, '<', '1970-01-01 00:00:01')
                        ->orWhere($column, '>', '2038-01-19 03:14:07');
                })->exists();

                if ($unsupported) {
                    throw new RuntimeException('Content dates outside the TIMESTAMP range must be resolved before rolling back this migration.');
                }
            }
        }

        foreach (self::COLUMNS as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->timestamp($column)->nullable()->change();
                }
            });
        }
    }
};
