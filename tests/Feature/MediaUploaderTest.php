<?php

use App\Models\Media;
use App\Services\MediaUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

it('stocke le fichier sur le disque public et cree le Media', function () {
    $media = app(MediaUploader::class)->fromUpload(UploadedFile::fake()->image('photo.jpg'), 'pages');

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->exists)->toBeTrue()
        ->and($media->original_name)->toBe('photo.jpg')
        ->and($media->mime_type)->toBe(Storage::disk('public')->mimeType($media->path))
        ->and($media->size)->toBeGreaterThan(0)
        ->and($media->path)->toStartWith('pages/');

    Storage::disk('public')->assertExists($media->path);
});

it('complete le Media avec les attributs supplementaires', function () {
    $media = app(MediaUploader::class)->fromUpload(
        UploadedFile::fake()->image('a.png'),
        'sliders',
        ['type' => 'image', 'apply_watermark' => true],
    );

    expect($media->type)->toBe('image')
        ->and($media->apply_watermark)->toBeTrue();
});

it('ne laisse pas les attributs supplementaires remplacer le chemin ni le nom', function () {
    $media = app(MediaUploader::class)->fromUpload(
        UploadedFile::fake()->image('vrai.jpg'),
        'pages',
        ['path' => 'ailleurs/faux.jpg', 'original_name' => 'faux.jpg'],
    );

    expect($media->path)->toStartWith('pages/')
        ->and($media->original_name)->toBe('vrai.jpg');
});

it('execute le hook sur le fichier stocke avant la creation du Media', function () {
    $received = null;

    $media = app(MediaUploader::class)->fromUpload(
        UploadedFile::fake()->image('a.jpg'),
        'pages',
        [],
        function (string $path) use (&$received) {
            $received = $path;
            expect(Media::count())->toBe(0);
            Storage::disk('public')->assertExists($path);
        },
    );

    // Le hook voit le fichier d'origine ; le Media peut pointer vers sa version WebP (meme nom, autre extension).
    expect(pathinfo($received, PATHINFO_FILENAME))->toBe(pathinfo($media->path, PATHINFO_FILENAME));
});

it('relit la taille du fichier apres le hook', function () {
    $media = app(MediaUploader::class)->fromUpload(
        UploadedFile::fake()->image('a.jpg'),
        'pages',
        [],
        fn (string $path) => Storage::disk('public')->put($path, str_repeat('x', 12345)),
    );

    expect($media->size)->toBe(12345);
});

it('complete le Media avec le tableau retourne par le hook, sans remplacer le chemin', function () {
    $media = app(MediaUploader::class)->fromUpload(
        UploadedFile::fake()->image('a.jpg'),
        'pages',
        ['type' => 'image'],
        fn (string $path) => ['width' => 7, 'height' => 5, 'path' => 'ailleurs/faux.jpg'],
    );

    expect($media->width)->toBe(7)
        ->and($media->height)->toBe(5)
        ->and($media->path)->toStartWith('pages/');
});

it('supprime le fichier stocke et relance l exception si le hook echoue', function () {
    $stored = null;
    $hook = function (string $path) use (&$stored) {
        $stored = $path;
        throw new LogicException('echec du hook');
    };

    expect(fn () => app(MediaUploader::class)->fromUpload(UploadedFile::fake()->image('a.jpg'), 'pages', [], $hook))
        ->toThrow(LogicException::class, 'echec du hook');

    expect($stored)->not->toBeNull()
        ->and(Media::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});
