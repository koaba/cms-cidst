<?php

use App\Models\Article;
use App\Models\NewsTicker;
use App\View\Components\NewsTicker as NewsTickerComponent;

it('inclut les articles publiés et récents dans le bandeau', function () {
    Article::factory()->create([
        'title' => 'Article récent',
        'is_published' => true,
        'published_at' => now()->subDays(2),
    ]);

    $component = new NewsTickerComponent();

    expect($component->items->pluck('content'))->toContain('Article récent');
});

it('exclut les articles non publiés', function () {
    Article::factory()->create([
        'title' => 'Article brouillon',
        'is_published' => false,
        'published_at' => now()->subDays(2),
    ]);

    $component = new NewsTickerComponent();

    expect($component->items->pluck('content'))->not->toContain('Article brouillon');
});

it('exclut les articles publiés il y a plus de 30 jours', function () {
    Article::factory()->create([
        'title' => 'Article périmé',
        'is_published' => true,
        'published_at' => now()->subDays(45),
    ]);

    $component = new NewsTickerComponent();

    expect($component->items->pluck('content'))->not->toContain('Article périmé');
});

it('limite à 5 le nombre d\'articles récents injectés', function () {
    Article::factory()->count(8)->create([
        'is_published' => true,
        'published_at' => now()->subDays(1),
    ]);

    $component = new NewsTickerComponent();

    $articlesCount = $component->items
        ->filter(fn ($item) => str_contains($item->link_url ?? '', '/blog/'))
        ->count();

    expect($articlesCount)->toBeLessThanOrEqual(5);
});

it('place les articles récents avant les tickers manuels', function () {
    Article::factory()->create([
        'title' => 'Article en tête',
        'is_published' => true,
        'published_at' => now(),
    ]);

    NewsTicker::factory()->create([
        'content' => 'Ticker manuel',
        'is_active' => true,
    ]);

    $component = new NewsTickerComponent();

    $positionArticle = $component->items->search(fn ($item) => $item->content === 'Article en tête');
    $positionTicker = $component->items->search(fn ($item) => $item->content === 'Ticker manuel');

    expect($positionArticle)->toBeLessThan($positionTicker);
});

it('inclut les tickers manuels actifs', function () {
    NewsTicker::factory()->create([
        'content' => 'Ticker actif',
        'is_active' => true,
    ]);

    $component = new NewsTickerComponent();

    expect($component->items->pluck('content'))->toContain('Ticker actif');
});

it('exclut les tickers manuels inactifs', function () {
    NewsTicker::factory()->create([
        'content' => 'Ticker inactif',
        'is_active' => false,
    ]);

    $component = new NewsTickerComponent();

    expect($component->items->pluck('content'))->not->toContain('Ticker inactif');
});