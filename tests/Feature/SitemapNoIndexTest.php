<?php

use App\Models\Article;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('sitemap.xml');
});

it('inclut un article publié dans le sitemap', function () {
    $article = Article::factory()->create([
        'is_published' => true,
        'title' => 'Article visible',
    ]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertSee($article->publicUrl(), false);
});

it('exclut du sitemap un article marqué no_index', function () {
    $article = Article::factory()->create([
        'is_published' => true,
        'title' => 'Article no-index',
    ]);
    $article->seo()->create(['no_index' => true]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertDontSee($article->publicUrl(), false);
});

it('exclut du sitemap une page marquée no_index', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'title' => 'Page no-index',
    ]);
    $page->seo()->create(['no_index' => true]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertDontSee($page->publicUrl(), false);
});