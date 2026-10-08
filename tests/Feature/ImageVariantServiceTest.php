<?php

use App\Services\ImageVariantService;
use Illuminate\Support\Facades\Storage;

function fakeJpeg(string $path, int $width, int $height): void
{
    $img = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($img);
    $bytes = ob_get_clean();
    Storage::disk('public')->put($path, $bytes);
}

it('genere des variantes webp sans jamais agrandir', function () {
    Storage::fake('public');
    fakeJpeg('media/photo.jpg', 1000, 600);

    $variants = (new ImageVariantService)->generate('media/photo.jpg');

    expect(array_keys($variants))->toBe([480, 768])
        ->and($variants[480])->toBe('media/variants/photo-480.webp')
        ->and($variants[768])->toBe('media/variants/photo-768.webp');

    Storage::disk('public')->assertExists('media/variants/photo-480.webp');
    Storage::disk('public')->assertExists('media/variants/photo-768.webp');
    Storage::disk('public')->assertMissing('media/variants/photo-1280.webp');

    expect(getimagesize(Storage::disk('public')->path($variants[480]))[0])->toBe(480)
        ->and(getimagesize(Storage::disk('public')->path($variants[768]))[0])->toBe(768)
        ->and(getimagesize(Storage::disk('public')->path($variants[480]))['mime'])->toBe('image/webp');
});

it('retourne un tableau vide si le fichier est absent', function () {
    Storage::fake('public');

    expect((new ImageVariantService)->generate('media/absent.jpg'))->toBe([]);
});
