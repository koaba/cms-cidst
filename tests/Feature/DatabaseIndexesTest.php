<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/** Vrai si un index de la table commence par ces colonnes (dans cet ordre). */
function hasLeadingIndex(string $table, array $columns): bool
{
    foreach (Schema::getIndexes($table) as $index) {
        if (array_slice($index['columns'], 0, count($columns)) === $columns) {
            return true;
        }
    }

    return false;
}

/** Details du plan d'execution SQLite d'une requete (jeu de donnees vide suffisant). */
function queryPlan(string $sql, array $bindings = []): string
{
    return collect(DB::select('EXPLAIN QUERY PLAN '.$sql, $bindings))->pluck('detail')->implode(' | ');
}

it('indexe les colonnes de tri et de filtre des requetes publiques', function (string $table, array $columns) {
    expect(hasLeadingIndex($table, $columns))->toBeTrue("Index manquant sur $table(".implode(', ', $columns).')');
})->with([
    'articles publies' => ['articles', ['is_published', 'published_at']],
    'menus par parent' => ['menus', ['parent_id', 'is_active', 'order']],
    'tickers actifs' => ['news_tickers', ['is_active', 'order']],
    'sliders actifs' => ['sliders', ['is_active', 'order']],
    'pivot mediables par media' => ['mediables', ['media_id']],
    'pivot categories par categorie' => ['article_category', ['category_id', 'article_id']],
]);

it('les requetes publiques utilisent ces index (plan SQLite)', function (string $sql, array $bindings) {
    if (DB::getDriverName() !== 'sqlite') {
        $this->markTestSkipped('Plan verifie sur SQLite uniquement.');
    }

    expect(queryPlan($sql, $bindings))->toMatch('/USING (COVERING )?INDEX/');
})->with([
    'sidebar et ticker : articles recents' => ['select * from "articles" where "is_published" = ? and "published_at" <= ? order by "published_at" desc limit 5', [1, '2026-10-30 12:00:00']],
    'ticker manuel' => ['select * from "news_tickers" where "is_active" = ? order by "order" asc', [1]],
    'sliders accueil' => ['select * from "sliders" where "is_active" = ? order by "order" asc limit 5', [1]],
    'enfants de menu' => ['select * from "menus" where "parent_id" = ? and "is_active" = ? order by "order" asc', [1, 1]],
    'usages d un media' => ['select * from "mediables" where "media_id" = ?', [1]],
    'articles d une categorie' => ['select * from "article_category" where "category_id" = ?', [1]],
]);
