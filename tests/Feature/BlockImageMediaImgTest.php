<?php

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

function renderBlockImage(Media $media, array $data = []): string
{
    return view('pages.blocks._image', ['media' => collect([$media]), 'data' => $data])->render();
}

it('affiche le bloc image via srcset avec alt, sizes et legende', function () {
    $media = Media::factory()->make([
        'path' => 'a/photo.jpg',
        'width' => 2000,
        'height' => 1000,
        'variants' => [480 => 'a/variants/photo-480.webp'],
    ]);

    $html = renderBlockImage($media, ['alt' => 'Texte alt', 'caption' => 'Ma legende']);

    expect($html)
        ->toContain('srcset="'.Storage::disk('public')->url('a/variants/photo-480.webp').' 480w')
        ->toContain('sizes="(min-width: 1024px) 768px, 100vw"')
        ->toContain('alt="Texte alt"')
        ->toContain('loading="lazy"')
        ->toContain('Ma legende');
});

it('reste valide sans variantes', function () {
    $media = Media::factory()->make(['path' => 'a/photo.jpg', 'width' => null, 'height' => null, 'variants' => null]);

    expect(renderBlockImage($media))
        ->toContain('src="'.Storage::disk('public')->url('a/photo.jpg').'"')
        ->not->toContain('srcset=');
});

it('n\'affiche rien sans media', function () {
    $html = view('pages.blocks._image', ['media' => collect(), 'data' => []])->render();

    expect(trim($html))->toBe('');
});