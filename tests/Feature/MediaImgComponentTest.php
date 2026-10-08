<?php

use App\Models\Media;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

function renderMediaImg(Media $media, string $attributes = ''): string
{
    return Blade::render('<x-media-img :media="$media" alt="Photo" '.$attributes.' />', ['media' => $media]);
}

function mediaImgUrl(string $path): string
{
    return Storage::disk('public')->url($path);
}

it('produit un srcset trie, sizes, dimensions et chargement differe', function () {
    $media = Media::factory()->make([
        'path' => 'a/photo.jpg',
        'width' => 2000,
        'height' => 1000,
        'variants' => [1280 => 'a/variants/photo-1280.webp', 480 => 'a/variants/photo-480.webp'],
    ]);

    $html = renderMediaImg($media, 'sizes="(min-width: 1024px) 768px, 100vw"');

    $srcset = mediaImgUrl('a/variants/photo-480.webp').' 480w, '
        .mediaImgUrl('a/variants/photo-1280.webp').' 1280w, '
        .mediaImgUrl('a/photo.jpg').' 2000w';

    expect($html)
        ->toContain('srcset="'.$srcset.'"')
        ->toContain('sizes="(min-width: 1024px) 768px, 100vw"')
        ->toContain('width="2000"')
        ->toContain('height="1000"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"')
        ->toContain('alt="Photo"');
});

it('se replie sur l\'original sans variantes ni dimensions', function () {
    $media = Media::factory()->make(['path' => 'a/photo.jpg', 'width' => null, 'height' => null, 'variants' => null]);

    $html = renderMediaImg($media);

    expect($html)
        ->toContain('src="'.mediaImgUrl('a/photo.jpg').'"')
        ->not->toContain('srcset=')
        ->not->toContain('width=')
        ->not->toContain('height=');
});

it('omet l\'original du srcset quand sa largeur est inconnue', function () {
    $media = Media::factory()->make([
        'path' => 'a/photo.jpg',
        'width' => null,
        'variants' => [480 => 'a/variants/photo-480.webp'],
    ]);

    expect(renderMediaImg($media))
        ->toContain('srcset="'.mediaImgUrl('a/variants/photo-480.webp').' 480w"');
});

it('priorise l\'image : fetchpriority high et jamais loading', function () {
    $media = Media::factory()->make(['path' => 'a/photo.jpg']);

    expect(renderMediaImg($media, ':priority="true"'))
        ->toContain('fetchpriority="high"')
        ->not->toContain('loading=');
});
