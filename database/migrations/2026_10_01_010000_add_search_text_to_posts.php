<?php

declare(strict_types=1);

use App\Support\SearchText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->longText('search_text')->nullable();
        });
        DB::table('posts')->select(['id', 'title', 'excerpt', 'body'])->orderBy('id')->chunkById(250, function ($posts): void {
            foreach ($posts as $post) {
                DB::table('posts')->where('id', $post->id)->update([
                    'search_text' => SearchText::fromPost($post->title, $post->excerpt, $post->body),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('search_text');
        });
    }
};
