<?php

use App\Services\PdfThumbnail\PopplerPdfThumbnailGenerator;

it('renvoie false sans exception quand le dossier temporaire est impossible a creer', function () {
    // Dossier de stockage isole : jamais le vrai storage/app/tmp, que d'autres tests (en parallele) utilisent aussi.
    $original = storage_path();
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cidst-pdfthumb-'.uniqid();
    mkdir($root.DIRECTORY_SEPARATOR.'app', 0777, true);
    app()->useStoragePath($root);

    $tmp = storage_path('app/tmp');
    $pdf = storage_path('app/portability-test.pdf');

    file_put_contents($pdf, "%PDF-1.0\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 10 10]/Parent 2 0 R>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
    file_put_contents($tmp, 'je suis un fichier, pas un dossier');

    $threw = false;
    $result = 'non-appele';

    try {
        $result = (new PopplerPdfThumbnailGenerator)->generate($pdf, storage_path('app/portability-out.jpg'), 400);
    } catch (Throwable) {
        $threw = true;
    } finally {
        app()->useStoragePath($original);
        @unlink($tmp);
        @unlink($pdf);
        @rmdir($root.DIRECTORY_SEPARATOR.'app');
        @rmdir($root);
    }

    expect($threw)->toBeFalse()
        ->and($result)->toBeFalse();
});
