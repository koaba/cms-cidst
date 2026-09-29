<?php

use App\Models\Page;

it('genere un sitemap valide avec une page mais sans aucun article', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/sitemap.xml');

    expect($response->getStatusCode())->toBe(200);
    expect(str_contains($response->getContent(), $page->publicUrl()))->toBeTrue();
});

it('fait apparaitre une nouvelle page publique dans le sitemap sans attendre le TTL', function () {
    $this->get('/sitemap.xml')->assertOk(); // met le sitemap en cache

    $page = Page::factory()->create([
        'title' => 'Page apres cache',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $body = $this->get('/sitemap.xml')->getContent();

    expect(str_contains($body, $page->publicUrl()))->toBeTrue();
});

it('retire une page du sitemap quand elle est supprimee', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $url = $page->publicUrl();

    expect(str_contains($this->get('/sitemap.xml')->getContent(), $url))->toBeTrue();

    $page->delete();

    expect(str_contains($this->get('/sitemap.xml')->getContent(), $url))->toBeFalse();
});