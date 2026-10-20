<?php

use App\Models\Article;
use App\Models\Page;

function sitemapSeoBody(): string
{
    return test()->get('/sitemap.xml')->assertOk()->getContent();
}

it('retire du sitemap un contenu passe en noindex via une nouvelle ligne seo', function (string $model) {
    $item = $model::factory()->create();
    expect(sitemapSeoBody())->toContain($item->publicUrl());

    $item->seo()->updateOrCreate([], ['no_index' => true]);

    expect(sitemapSeoBody())->not->toContain($item->publicUrl());
})->with(['article' => Article::class, 'page' => Page::class]);

it('retire du sitemap un contenu passe en noindex via une ligne seo existante', function (string $model) {
    $item = $model::factory()->create();
    $item->seo()->create(['no_index' => false]);
    expect(sitemapSeoBody())->toContain($item->publicUrl());

    $item->seo()->updateOrCreate([], ['no_index' => true]);

    expect(sitemapSeoBody())->not->toContain($item->publicUrl());
})->with(['article' => Article::class, 'page' => Page::class]);
