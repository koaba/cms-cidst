<?php

use App\Services\PdfThumbnail\PdfThumbnailProcessor;
use Illuminate\Support\Facades\Storage;

it('ne lance pas d exception quand le driver de miniature PDF est inconnu', function () {
    Storage::fake('public');
    config()->set('services.pdf_thumbnail.driver', 'inconnu');

    $generator = (new PdfThumbnailProcessor)->pdfThumbnailGenerator('articles/pdfs');

    $threw = false;
    $result = 'non-appele';

    try {
        $result = $generator('articles/pdfs/x.pdf');
    } catch (Throwable) {
        $threw = true;
    }

    expect($threw)->toBeFalse()
        ->and($result)->toBeNull();
});
