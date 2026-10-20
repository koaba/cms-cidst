<?php

use App\Models\Article;
use App\Models\Page;

function sitemapBody(): string
{
    return test()->get('/sitemap.xml')->assertOk()->getContent();
}

it('liste le contenu publie et exclut brouillons et noindex', function () {
    $article = Article::factory()->create();
    $page = Page::factory()->create();
    $draft = Article::factory()->create(['is_published' => false]);
    $hidden = Page::factory()->create();
    $hidden->seo()->create(['no_index' => true]);

    $body = sitemapBody();

    expect($body)
        ->toContain($article->publicUrl())
        ->toContain($page->publicUrl())
        ->not->toContain($draft->publicUrl())
        ->not->toContain($hidden->publicUrl());
});

it('inclut un contenu cree apres la mise en cache', function (string $model) {
    sitemapBody();

    $item = $model::factory()->create();

    expect(sitemapBody())->toContain($item->publicUrl());
})->with(['article' => Article::class, 'page' => Page::class]);

it('retire un contenu depublie apres la mise en cache', function (string $model) {
    $item = $model::factory()->create();
    expect(sitemapBody())->toContain($item->publicUrl());

    $item->update(['is_published' => false]);

    expect(sitemapBody())->not->toContain($item->publicUrl());
})->with(['article' => Article::class, 'page' => Page::class]);

it('retire un contenu supprime apres la mise en cache', function (string $model) {
    $item = $model::factory()->create();
    $url = $item->publicUrl();
    expect(sitemapBody())->toContain($url);

    $item->delete();

    expect(sitemapBody())->not->toContain($url);
})->with(['article' => Article::class, 'page' => Page::class]);
