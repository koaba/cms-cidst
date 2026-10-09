<?php

use App\Models\Media;
use Illuminate\Support\Collection;

function renderBlockHero(Collection $media, array $data = []): string
{
    return view('pages.blocks._banniere_hero', [
        'media' => $media,
        'data' => array_merge(['titre' => 'Titre du hero'], $data),
    ])->render();
}

it('charge l\'image du hero en priorite, sans lazy, en pleine largeur', function () {
    $media = Media::factory()->make([
        'path' => 'a/hero.jpg',
        'width' => 2000,
        'height' => 1000,
        'variants' => [480 => 'a/variants/hero-480.webp'],
    ]);

    $html = renderBlockHero(collect([$media]), ['alt' => 'Texte alt']);

    expect($html)
        ->toContain('srcset="')
        ->toContain('sizes="100vw"')
        ->toContain('fetchpriority="high"')
        ->toContain('alt="Texte alt"')
        ->not->toContain('loading=');
});

it('affiche le hero sans image : fond gris et aucune balise img', function () {
    $html = renderBlockHero(collect());

    expect($html)
        ->toContain('bg-gray-800')
        ->toContain('Titre du hero')
        ->not->toContain('<img');
});

it('applique l\'opacite du calque', function () {
    $media = Media::factory()->make(['path' => 'a/hero.jpg']);

    expect(renderBlockHero(collect([$media]), ['overlay_opacity' => 60]))
        ->toContain('opacity: 0.6');
});
