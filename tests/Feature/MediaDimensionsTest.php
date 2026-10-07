<?php

use App\Models\Media;
use App\Services\BlockMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function storeMediaForDimensionsTest(UploadedFile $file, string $type): Media
{
    $service = app(BlockMediaService::class);

    return (new ReflectionMethod($service, 'storeMedia'))->invoke($service, $file, $type);
}

it('enregistre les dimensions d\'une image', function () {
    $media = storeMediaForDimensionsTest(UploadedFile::fake()->image('a.jpg', 120, 80), 'image');

    expect($media->fresh()->width)->toBe(120)
        ->and($media->fresh()->height)->toBe(80);
});

it('laisse les dimensions a null pour les non-images', function () {
    storeMediaForDimensionsTest(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'document');
    storeMediaForDimensionsTest(UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'), 'document');
    storeMediaForDimensionsTest(UploadedFile::fake()->image('c.jpg', 50, 40), 'image');

    expect(Media::whereNull('width')->count())->toBe(2)
        ->and(Media::whereNotNull('width')->count())->toBe(1);
});

it('ne leve pas d\'exception pour une image illisible', function () {
    $media = storeMediaForDimensionsTest(
        UploadedFile::fake()->createWithContent('bad.jpg', 'pas une image'),
        'image'
    );

    expect($media->fresh()->width)->toBeNull()
        ->and($media->fresh()->height)->toBeNull();
});
