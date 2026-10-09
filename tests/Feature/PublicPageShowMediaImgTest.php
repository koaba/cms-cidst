<?php

use App\Models\Media;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pageWithImage(array $mediaAttributes): Page
{
    $page = Page::factory()->create(['title' => 'Ma page', 'published_at' => '2026-01-01']);
    $page->media()->attach(Media::factory()->create($mediaAttributes + ['type' => 'image']));

    return $page;
}

it('affiche l\'image de la page via srcset, prioritaire, avec alt et dimensions', function () {
    $page = pageWithImage([
        'path' => 'p/photo.jpg',
        'width' => 2000,
        'height' => 1000,
        'variants' => [480 => 'p/variants/photo-480.webp'],
    ]);

    $html = $this->get(route('pages.show', $page))->assertOk()->getContent();

    expect($html)
        ->toContain('srcset="'.Storage::disk('public')->url('p/variants/photo-480.webp').' 480w')
        ->toContain('sizes="(min-width: 672px) 672px, 100vw"')
        ->toContain('fetchpriority="high"')
        ->toContain('alt="Ma page"')
        ->toContain('width="2000"')
        ->toContain('height="1000"')
        ->not->toContain('loading="lazy"');
});

it('reste valide sans variantes ni dimensions', function () {
    $page = pageWithImage(['path' => 'p/photo.jpg', 'width' => null, 'height' => null, 'variants' => null]);

    $html = $this->get(route('pages.show', $page))->assertOk()->getContent();

    expect($html)
        ->toContain('src="'.Storage::disk('public')->url('p/photo.jpg').'"')
        ->toContain('fetchpriority="high"')
        ->not->toContain('srcset="');
});
