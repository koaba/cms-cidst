<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\Menu;
use App\Models\NewsTicker;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Cache::flush();
});

/** Simule une nouvelle requete HTTP : l'app de test est partagee, pas la prod. */
function freshRequest(string $url = '/contact'): TestResponse
{
    app()->forgetScopedInstances();
    app()->forgetInstance('site-settings.current');

    return test()->get($url);
}

function layoutQueries(string $url = '/contact'): array
{
    $tables = [];
    DB::listen(function ($query) use (&$tables) {
        if (preg_match('/from [`"]?(menus|articles|news_tickers|site_settings|media)[`"]?/i', $query->sql, $m)) {
            $tables[] = strtolower($m[1]);
        }
    });

    freshRequest($url)->assertOk();

    return $tables;
}

function publishedArticle(array $attrs = []): Article
{
    return Article::factory()->create(array_merge([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ], $attrs));
}

it('ne requete plus menu, ticker, sidebar ni reglages une fois le cache chaud', function () {
    Menu::factory()->create(['label' => 'Accueil', 'target' => '/']);
    publishedArticle();
    NewsTicker::create(['content' => 'Info', 'is_active' => true, 'order' => 1]);

    layoutQueries();

    expect(layoutQueries())->toBe([]);
});

it('invalide le cache quand un element de menu change', function () {
    $menu = Menu::factory()->create(['label' => 'Ancien libelle', 'target' => '/']);
    freshRequest()->assertSee('Ancien libelle');

    $menu->update(['label' => 'Nouveau libelle']);

    freshRequest()->assertSee('Nouveau libelle')->assertDontSee('Ancien libelle');
});

it('invalide le cache quand un element de menu est supprime', function () {
    $menu = Menu::factory()->create(['label' => 'A supprimer', 'target' => '/']);
    freshRequest()->assertSee('A supprimer');

    $menu->delete();

    freshRequest()->assertDontSee('A supprimer');
});

it('invalide le cache quand un article publie apparait dans le ticker et la sidebar', function () {
    freshRequest()->assertOk();

    publishedArticle(['title' => 'Article tout neuf']);

    freshRequest()->assertSee('Article tout neuf');
});

it('invalide le cache quand un ticker manuel change', function () {
    $ticker = NewsTicker::create(['content' => 'Message initial', 'is_active' => true, 'order' => 1]);
    freshRequest()->assertSee('Message initial');

    $ticker->update(['content' => 'Message modifie']);

    freshRequest()->assertSee('Message modifie')->assertDontSee('Message initial');
});

it('invalide le cache quand les reglages du site changent', function () {
    SiteSetting::current()->update(['news_ticker_direction' => 'horizontal']);
    NewsTicker::create(['content' => 'Defilant', 'is_active' => true, 'order' => 1]);
    freshRequest()->assertSee('animate-marquee');

    SiteSetting::current()->update(['news_ticker_direction' => 'vertical']);

    freshRequest()->assertSee('animate-marquee-vertical');
});

it('invalide la sidebar quand le media de couverture change', function () {
    $media = Media::factory()->create(['path' => 'articles/ancien.jpg', 'type' => 'image']);
    $article = publishedArticle(['image' => 'articles/raw.jpg']);
    $article->coverMedia()->associate($media)->save();
    freshRequest('/pages')->assertSee('ancien');

    $media->update(['path' => 'articles/nouveau.jpg']);

    freshRequest('/pages')->assertSee('nouveau')->assertDontSee('ancien.jpg');
});

it('fait apparaitre un article programme a l echeance du cache', function () {
    publishedArticle(['title' => 'Article programme', 'published_at' => now()->addMinutes(2)]);
    freshRequest()->assertDontSee('Article programme');

    $this->travel(config('display.layout_cache_ttl') + 180)->seconds();

    freshRequest()->assertSee('Article programme');
});
