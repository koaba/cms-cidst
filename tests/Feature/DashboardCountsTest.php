<?php

use App\Models\Article;
use App\Models\Page;
use App\Models\User;
use App\Services\Dashboard\DashboardStatsService;

function dcntStats(): array
{
    return app(DashboardStatsService::class)->forPeriod(null)['stats'];
}

function dcntArticle(array $attributes = []): Article
{
    return Article::create($attributes + [
        'title' => 'Article '.uniqid(),
        'slug' => 'article-'.uniqid(),
        'content' => 'Contenu',
        'user_id' => User::query()->first()?->id ?? User::factory()->create()->id,
        'is_published' => true,
    ]);
}

it('ne compte pas les pages programmees ni les brouillons dans la carte Pages', function () {
    Page::factory()->create(['published_at' => now()->subDay()]);
    Page::factory()->create(['published_at' => null]);
    Page::factory()->create(['published_at' => now()->addWeek()]);
    Page::factory()->create(['is_published' => false]);

    expect(dcntStats()['pages'])->toBe(2);
});

it('ne compte pas les articles programmes ni les brouillons dans la carte Articles', function () {
    dcntArticle(['published_at' => now()->subDay()]);
    dcntArticle(['published_at' => null]);
    dcntArticle(['published_at' => now()->addWeek()]);
    dcntArticle(['is_published' => false]);

    expect(dcntStats()['articles'])->toBe(2);
});

it('ne calcule plus views_30d, lu nulle part', function () {
    expect(dcntStats())->not->toHaveKey('views_30d');
});
