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
        ->and($media->mime_type)->toBe('image/jpeg')
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
