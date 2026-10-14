<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function mediaQueryCount(string $url): int
{
    $count = 0;
    DB::listen(function ($query) use (&$count) {
        if (preg_match('/from [`"]?media[`"]?\s+where/i', $query->sql)) {
            $count++;
        }
    });

    test()->get($url)->assertOk();

    return $count;
}

function threeArticlesWithCover(?Category $category = null): void
{
    foreach (range(1, 3) as $i) {
        $article = Article::factory()->create([
            'is_published' => true,
            'published_at' => now()->subDay(),
            'image' => 'articles/raw.jpg',
        ]);
        $article->coverMedia()->associate(Media::factory()->create(['type' => 'image', 'path' => "articles/c{$i}.jpg"]))->save();
        $category?->articles()->attach($article);
    }
}

it('charge les couvertures de la liste en une seule requete media', function () {
    threeArticlesWithCover();

    expect(mediaQueryCount(route('blog.index')))->toBeLessThanOrEqual(2);
});

it('charge les couvertures de la page categorie en une seule requete media', function () {
    $category = Category::factory()->create();
    threeArticlesWithCover($category);

    expect(mediaQueryCount(route('blog.category', $category)))->toBeLessThanOrEqual(2);
});
