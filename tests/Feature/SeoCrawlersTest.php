<?php

use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

it("référence l'accueil dans le sitemap", function () {
    Cache::forget('sitemap.xml');

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertSee('<loc>'.url('/').'</loc>', false)
        ->assertDontSee('<lastmod></lastmod>', false);
});

it("sert robots.txt dynamiquement avec l'URL du sitemap", function () {
    URL::forceRootUrl('http://exemple.test');

    $response = $this->get('/robots.txt');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toStartWith('text/plain')
        ->and($response->getContent())
        ->toContain('Disallow: /admin')
        ->toContain('Sitemap: http://exemple.test/sitemap.xml');
});

it("n'a aucun robots.txt statique qui masquerait la route", function () {
    expect(public_path('robots.txt'))->not->toBeFile();
});

it('retire du sitemap une page dont no_index est bascule seul', function () {
    Cache::forget('sitemap.xml');

    $page = Page::factory()->create();
    $page->seo()->updateOrCreate([], ['no_index' => false]);

    $this->get('/sitemap.xml')->assertOk()->assertSee($page->publicUrl(), false);

    $page->seo->update(['no_index' => true]);

    $this->get('/sitemap.xml')->assertOk()->assertDontSee($page->publicUrl(), false);
});
