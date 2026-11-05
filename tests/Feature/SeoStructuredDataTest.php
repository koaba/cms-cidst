<?php

use App\Models\Article;
use App\Models\Page;
use App\Models\SiteSetting;

beforeEach(function () {
    $this->blocks = function (string $url): array {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $matches);

        return array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
    };

    $this->withLogo = fn () => SiteSetting::current()->update(['logo_path' => 'logos/cidst.png']);
    $this->logoUrl = asset('storage/logos/cidst.png');
});

it('emet Organization et WebSite sur l accueil', function () {
    ($this->withLogo)();

    $blocks = ($this->blocks)('/');
    expect($blocks)->toHaveCount(1);

    $graph = collect($blocks[0]['@graph']);
    $organization = $graph->firstWhere('@type', 'Organization');
    $website = $graph->firstWhere('@type', 'WebSite');

    expect($organization['name'])->toBe(config('app.name'))
        ->and($organization['url'])->toBe(url('/'))
        ->and($organization['logo'])->toBe($this->logoUrl)
        ->and($website['url'])->toBe(url('/'))
        ->and($website['inLanguage'])->toBe('fr')
        ->and($website['publisher']['@id'])->toBe($organization['@id']);
});

it('omet le logo de l organisation quand aucun n est defini', function () {
    $blocks = ($this->blocks)('/');
    expect($blocks)->toHaveCount(1);

    expect(collect($blocks[0]['@graph'])->firstWhere('@type', 'Organization'))->not->toHaveKey('logo');
});

it('emet un fil d Ariane sur un article', function () {
    $article = Article::factory()->create(['title' => 'Mon article', 'slug' => 'mon-article', 'published_at' => now()->subDay()]);

    $breadcrumb = collect(($this->blocks)(route('blog.show', $article)))->firstWhere('@type', 'BreadcrumbList');

    expect($breadcrumb)->not->toBeNull()
        ->and(collect($breadcrumb['itemListElement'])->map(fn (array $item) => [$item['position'], $item['name'], $item['item']])->all())
        ->toBe([
            [1, 'Accueil', url('/')],
            [2, 'Blog', route('blog.index')],
            [3, 'Mon article', route('blog.show', $article)],
        ]);
});

it('emet un fil d Ariane sur une page', function () {
    $page = Page::factory()->create(['title' => 'A propos']);

    $breadcrumb = collect(($this->blocks)(route('pages.show', $page)))->firstWhere('@type', 'BreadcrumbList');

    expect($breadcrumb)->not->toBeNull()
        ->and(collect($breadcrumb['itemListElement'])->map(fn (array $item) => [$item['position'], $item['name'], $item['item']])->all())
        ->toBe([
            [1, 'Accueil', url('/')],
            [2, 'A propos', route('pages.show', $page)],
        ]);
});

it('complete l auteur et l editeur de l article avec l url et le logo', function () {
    ($this->withLogo)();
    $article = Article::factory()->create(['slug' => 'article-editeur', 'published_at' => now()->subDay()]);

    $data = collect(($this->blocks)(route('blog.show', $article)))->firstWhere('@type', 'NewsArticle');
    $organization = ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/'), 'logo' => $this->logoUrl];

    expect($data['publisher'])->toBe($organization)
        ->and($data['author'])->toBe($organization);
});

it('utilise le logo comme image sociale par defaut', function () {
    ($this->withLogo)();
    $page = Page::factory()->create();

    $this->get(route('pages.show', $page))->assertOk()
        ->assertSee('<meta property="og:image" content="'.$this->logoUrl.'">', false)
        ->assertSee('<meta name="twitter:image" content="'.$this->logoUrl.'">', false)
        ->assertSee('<meta name="twitter:card" content="summary">', false);
});

it('n emet pas d image sociale sans image ni logo', function () {
    $page = Page::factory()->create();

    $this->get(route('pages.show', $page))->assertOk()
        ->assertDontSee('og:image', false)
        ->assertDontSee('twitter:image', false);
});

it('definit les libelles du fil d Ariane dans la config', function () {
    expect(config('seo.breadcrumbs'))->toBeArray()->toHaveKeys(['home', 'blog']);
});
