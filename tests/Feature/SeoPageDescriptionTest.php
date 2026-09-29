<?php

use App\Models\Page;
use App\Services\SeoService;

it('derive la description du premier bloc texte quand content est vide', function () {
    $page = Page::factory()->create(['content' => '']);
    $page->blocks()->create([
        'type' => 'texte',
        'data' => ['content' => '<p>Bonjour le monde</p>'],
        'order' => 0,
    ]);

    expect(SeoService::description($page->fresh()))->toBe('Bonjour le monde');
});

it('garde content quand il est renseigné', function () {
    $page = Page::factory()->create(['content' => 'Contenu principal']);
    $page->blocks()->create([
        'type' => 'texte',
        'data' => ['content' => 'Texte du bloc'],
        'order' => 0,
    ]);

    expect(SeoService::description($page->fresh()))->toBe('Contenu principal');
});

it('renvoie null sans content ni bloc porteur de texte', function () {
    $page = Page::factory()->create(['content' => '']);
    $page->blocks()->create([
        'type' => 'separateur',
        'data' => ['style' => 'fin'],
        'order' => 0,
    ]);

    expect(SeoService::description($page->fresh()))->toBeNull();
});