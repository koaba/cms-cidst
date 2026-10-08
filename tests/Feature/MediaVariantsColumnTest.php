<?php

use App\Models\Media;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function imageMedia(array $extra = []): Media
{
    return Media::create(array_merge([
        'path' => 'media/a.jpg',
        'original_name' => 'a.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ], $extra));
}

it('stocke variants sous forme de tableau', function () {
    Queue::fake();

    $media = imageMedia(['variants' => [480 => 'media/variants/a-480.webp', 1280 => 'media/variants/a-1280.webp']]);

    expect($media->fresh()->variants)->toBe([480 => 'media/variants/a-480.webp', 1280 => 'media/variants/a-1280.webp']);
});

it('supprime les fichiers de variantes avec le media', function () {
    Queue::fake();
    Storage::fake('public');
    Storage::disk('public')->put('media/a.jpg', 'x');
    Storage::disk('public')->put('media/variants/a-480.webp', 'x');
    Storage::disk('public')->put('media/variants/a-768.webp', 'y');

    $media = imageMedia(['variants' => [480 => 'media/variants/a-480.webp', 768 => 'media/variants/a-768.webp']]);
    $media->delete();

    Storage::disk('public')->assertMissing('media/a.jpg');
    Storage::disk('public')->assertMissing('media/variants/a-480.webp');
    Storage::disk('public')->assertMissing('media/variants/a-768.webp');
});
