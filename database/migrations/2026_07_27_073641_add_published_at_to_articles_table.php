<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_published');
        });

        // Backfill : les articles existants gardent leur date de création comme date de publication
        \App\Models\Article::whereNull('published_at')->get()->each(function ($article) {
            $article->timestamps = false;
            $article->update(['published_at' => $article->created_at]);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};