<?php

use App\Jobs\ProcessMediaImage;
use App\Models\Media;
use App\Services\ImageVariantService;
use App\Services\ThumbnailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Queue::fake();
    Storage::fake('public');
});

it('dispatche un seul job pour une image', function () {
    Media::create([
        'path' => 'media/a.jpg',
        'original_name' => 'a.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);

    Queue::assertPushed(ProcessMediaImage::class, 1);
    Queue::assertCount(1);
});

it('ne dispatche pas pour une video', function () {
    Media::create([
        'path' => 'media/v.mp4',
        'original_name' => 'v.mp4',
        'mime_type' => 'video/mp4',
        'size' => 1000,
        'type' => 'video',
    ]);

    Queue::assertNotPushed(ProcessMediaImage::class);
});

/** Cree un Media image avec un vrai fichier JPEG de la largeur donnee. */
$imageMedia = function (int $width, int $height = 600): Media {
    $img = imagecreatetruecolor($width, $height);
    ob_start();
    imagejpeg($img);
    Storage::disk('public')->put('media/photo.jpg', ob_get_clean());

    return Media::create([
        'path' => 'media/photo.jpg',
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);
};

it('genere la miniature et les variantes en une seule ecriture', function () use ($imageMedia) {
    $media = $imageMedia(1000);

    $writes = 0;
    DB::listen(function ($query) use (&$writes) {
        if (preg_match('/^update [`"]media[`"]/i', $query->sql)) {
            $writes++;
        }
    });

    (new ProcessMediaImage($media))->handle(app(ThumbnailService::class), app(ImageVariantService::class));

    expect($media->fresh()->thumbnail_path)->toBe('media/thumbnails/photo.jpg')
        ->and($media->fresh()->variants)->toBe([
            480 => 'media/variants/photo-480.webp',
            768 => 'media/variants/photo-768.webp',
        ])
        ->and($writes)->toBe(1);

    Storage::disk('public')->assertExists('media/thumbnails/photo.jpg');
});

it('garde les variantes quand la miniature echoue', function () use ($imageMedia) {
    $media = $imageMedia(1000);

    $this->mock(ThumbnailService::class, fn ($mock) => $mock->shouldReceive('generate')->andReturn(null));

    (new ProcessMediaImage($media))->handle(app(ThumbnailService::class), app(ImageVariantService::class));

    expect($media->fresh()->thumbnail_path)->toBeNull()
        ->and($media->fresh()->variants)->toHaveCount(2);
});

it('garde la miniature quand aucune variante n\'est possible', function () use ($imageMedia) {
    $media = $imageMedia(300, 200);

    (new ProcessMediaImage($media))->handle(app(ThumbnailService::class), app(ImageVariantService::class));

    expect($media->fresh()->thumbnail_path)->toBe('media/thumbnails/photo.jpg')
        ->and($media->fresh()->variants)->toBeNull();
});

it('ignore un media supprime avant l\'execution du job', function () {
    $media = Media::create([
        'path' => 'media/gone.jpg',
        'original_name' => 'gone.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);
    $job = new ProcessMediaImage($media);
    $media->delete();

    $job->handle(app(ThumbnailService::class), app(ImageVariantService::class));

    expect(Media::find($media->id))->toBeNull();
});
