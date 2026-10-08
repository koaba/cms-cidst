<?php

use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use App\Services\ImageVariantService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('dispatche la generation des variantes pour une image', function () {
    Queue::fake();

    Media::create([
        'path' => 'media/a.jpg',
        'original_name' => 'a.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);

    Queue::assertPushed(GenerateMediaVariants::class);
});

it('ne dispatche pas pour une video', function () {
    Queue::fake();

    Media::create([
        'path' => 'media/v.mp4',
        'original_name' => 'v.mp4',
        'mime_type' => 'video/mp4',
        'size' => 1000,
        'type' => 'video',
    ]);

    Queue::assertNotPushed(GenerateMediaVariants::class);
});

it('stocke les variantes generees dans la colonne variants', function () {
    Queue::fake();
    Storage::fake('public');

    $img = imagecreatetruecolor(1000, 600);
    ob_start();
    imagejpeg($img);
    Storage::disk('public')->put('media/photo.jpg', ob_get_clean());

    $media = Media::create([
        'path' => 'media/photo.jpg',
        'original_name' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);

    (new GenerateMediaVariants($media))->handle(app(ImageVariantService::class));

    expect($media->fresh()->variants)->toBe([
        480 => 'media/variants/photo-480.webp',
        768 => 'media/variants/photo-768.webp',
    ]);
});

it('ignore un media supprime avant l\'execution du job', function () {
    Queue::fake();

    $media = Media::create([
        'path' => 'media/gone.jpg',
        'original_name' => 'gone.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);
    $job = new GenerateMediaVariants($media);
    $media->delete();

    $job->handle(app(ImageVariantService::class));

    expect(Media::find($media->id))->toBeNull();
});
