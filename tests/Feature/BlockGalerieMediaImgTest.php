<?php

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

function galerieItem(array $attributes = []): Media
{
    $media = Media::factory()->make(array_merge([
        'path' => 'a/photo.jpg',
        'width' => 2000,
        'height' => 1000,
        'variants' => [480 => 'a/variants/photo-480.webp'],
    ], $attributes));

    $media->setRelation('pivot', (object) ['alt' => 'Alt galerie', 'caption' => 'Legende galerie']);

    return $media;
}

function renderBlockGalerie(string $layout, array $items): string
{
    return view('pages.blocks._galerie', [
        'block' => (object) ['id' => 7],
        'data' => ['layout' => $layout],
        'media' => collect($items),
    ])->render();
}

it('affiche la grille via srcset avec sizes, alt et legende', function () {
    $html = renderBlockGalerie('grid', [galerieItem()]);

    expect($html)
        ->toContain('srcset="'.Storage::disk('public')->url('a/variants/photo-480.webp').' 480w')
        ->toContain('sizes="(min-width: 768px) 33vw, 50vw"')
        ->toContain('alt="Alt galerie"')
        ->toContain('loading="lazy"')
        ->toContain('Legende galerie')
        ->not->toContain('carousel-7');
});

it('affiche le carrousel via srcset avec sizes, alt et legende', function () {
    $html = renderBlockGalerie('carousel', [galerieItem()]);

    expect($html)
        ->toContain('id="carousel-7"')
        ->toContain('srcset="')
        ->toContain('sizes="288px"')
        ->toContain('alt="Alt galerie"')
        ->toContain('Legende galerie');
});

it('reste valide sans variantes', function () {
    $html = renderBlockGalerie('grid', [galerieItem(['variants' => null, 'width' => null, 'height' => null])]);

    expect($html)
        ->toContain('src="'.Storage::disk('public')->url('a/photo.jpg').'"')
        ->not->toContain('srcset=');
});
