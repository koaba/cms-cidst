<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pagesIndexWithImage(array $mediaAttributes, int $columns): Page
{
    SiteSetting::current()->forceFill(['pages_grid_columns' => $columns])->save();
    Cache::flush();

    $page = Page::factory()->create(['title' => 'Ma page', 'published_at' => '2026-01-01']);
    $page->media()->attach(Media::factory()->create($mediaAttributes + ['type' => 'image']));

    return $page;
}

it("affiche l'image de chaque carte via srcset, avec sizes selon la grille", function (int $columns, string $sizes) {
    pagesIndexWithImage([
        'path' => 'p/photo.jpg',
        'width' => 2000,
        'height' => 1000,
        'variants' => [480 => 'p/variants/photo-480.webp'],
    ], $columns);

    $html = $this->get(route('pages.index'))->assertOk()->getContent();

    expect($html)
        ->toContain('srcset="'.Storage::disk('public')->url('p/variants/photo-480.webp').' 480w')
        ->toContain('sizes="'.$sizes.'"')
        ->toContain('alt="Ma page"')
        ->toContain('width="2000"')
        ->toContain('loading="lazy"');
})->with([
    '2 colonnes' => [2, '(min-width: 640px) 50vw, 100vw'],
    '3 colonnes' => [3, '(min-width: 640px) 33vw, 100vw'],
    '4 colonnes' => [4, '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw'],
]);

it('reste valide sans variantes : dimensions presentes, pas de srcset', function () {
    pagesIndexWithImage(['path' => 'p/photo.jpg', 'width' => 800, 'height' => 400, 'variants' => null], 2);

    $html = $this->get(route('pages.index'))->assertOk()->getContent();

    expect($html)
        ->toContain('src="'.Storage::disk('public')->url('p/photo.jpg').'"')
        ->toContain('width="800"')
        ->toContain('height="400"')
        ->not->toContain('srcset="');
});
