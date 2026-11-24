<?php

use App\Models\Article;
use App\Models\PageView;
use App\Support\SqlDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('produit une expression strftime sous sqlite', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(SqlDate::bucket('viewed_at', 'Y-m'))->toBe('strftime(\'%Y-%m\', "viewed_at")')
        ->and(SqlDate::bucket('viewed_at', 'Y-m-d'))->toBe('strftime(\'%Y-%m-%d\', "viewed_at")');
});

it('echappe le nom de colonne selon la grammaire', function () {
    expect(SqlDate::bucket('page_views.viewed_at', 'Y-m'))
        ->toBe('strftime(\'%Y-%m\', "page_views"."viewed_at")');
});

it('refuse un format non gere', function () {
    SqlDate::bucket('viewed_at', 'd/m/Y');
})->throws(InvalidArgumentException::class, 'Format de date non gere : d/m/Y');

it('regroupe reellement les lignes par mois et par jour', function () {
    $article = Article::factory()->create();

    foreach (['2026-01-05 10:00:00', '2026-01-05 18:00:00', '2026-01-20 09:00:00', '2026-02-01 08:00:00'] as $i => $at) {
        PageView::create([
            'viewable_type' => Article::class,
            'viewable_id' => $article->id,
            'ip_hash' => "h{$i}",
            'viewed_at' => $at,
        ]);
    }

    $byMonth = PageView::query()
        ->selectRaw(SqlDate::bucket('viewed_at', 'Y-m').' as bucket, count(*) as total')
        ->groupBy('bucket')->orderBy('bucket')
        ->pluck('total', 'bucket')->all();

    $byDay = PageView::query()
        ->selectRaw(SqlDate::bucket('viewed_at', 'Y-m-d').' as bucket, count(*) as total')
        ->groupBy('bucket')->orderBy('bucket')
        ->pluck('total', 'bucket')->all();

    expect($byMonth)->toBe(['2026-01' => 3, '2026-02' => 1])
        ->and($byDay)->toBe(['2026-01-05' => 2, '2026-01-20' => 1, '2026-02-01' => 1]);
});
