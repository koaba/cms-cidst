<?php

use App\Services\PdfThumbnail\PdfThumbnailGeneratorFactory;
use Illuminate\Support\Facades\Storage;

function makeThumbnailGenerator(string $driver): array
{
    config()->set('services.pdf_thumbnail.driver', $driver);

    try {
        return ['generator' => PdfThumbnailGeneratorFactory::make(), 'threw' => false];
    } catch (Throwable) {
        return ['generator' => null, 'threw' => true];
    }
}

it('fournit un generateur nul avec le driver none', function () {
    Storage::fake('public');
    $made = makeThumbnailGenerator('none');

    expect($made['threw'])->toBeFalse();

    $out = Storage::disk('public')->path('thumbs/x.jpg');
    $result = $made['generator']?->generate(Storage::disk('public')->path('x.pdf'), $out, 400);

    expect($result)->toBeFalse()
        ->and(file_exists($out))->toBeFalse();
});

it('retombe sur le generateur nul quand le driver est inconnu', function () {
    $made = makeThumbnailGenerator('inconnu');

    expect($made['threw'])->toBeFalse()
        ->and($made['generator'])->not->toBeNull();
});
