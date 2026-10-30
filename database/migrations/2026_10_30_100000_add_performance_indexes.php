<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index des requetes du site public et du dashboard (filtres + tri).
 * Chaque index n'est cree que si aucun index existant ne commence deja par les
 * memes colonnes (MySQL cree deja un index pour chaque cle etrangere).
 */
return new class extends Migration
{
    /** @var array<string, array<string, list<string>>> table => [nom d'index => colonnes] */
    private const INDEXES = [
        'articles' => ['articles_is_published_published_at_index' => ['is_published', 'published_at']],
        'menus' => ['menus_parent_id_is_active_order_index' => ['parent_id', 'is_active', 'order']],
        'news_tickers' => ['news_tickers_is_active_order_index' => ['is_active', 'order']],
        'sliders' => ['sliders_is_active_order_index' => ['is_active', 'order']],
        'mediables' => ['mediables_media_id_index' => ['media_id']],
        'article_category' => ['article_category_category_id_article_id_index' => ['category_id', 'article_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if ($this->hasLeadingIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }

    private function hasLeadingIndex(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (array_slice($index['columns'], 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }
};
