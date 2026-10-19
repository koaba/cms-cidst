<?php

use App\Services\PdfThumbnail\PopplerPdfThumbnailGenerator;

it('renvoie false sans exception quand le dossier temporaire est impossible a creer', function () {
    $tmp = storage_path('app/tmp');
    $backup = $tmp.'_backup_'.uniqid();
    $hadTmp = file_exists($tmp);
    $pdf = storage_path('app/portability-test-'.uniqid().'.pdf');

    file_put_contents($pdf, "%PDF-1.0\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 10 10]/Parent 2 0 R>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");

    if ($hadTmp) {
        rename($tmp, $backup);
    }
    file_put_contents($tmp, 'je suis un fichier, pas un dossier');

    $threw = false;
    $result = 'non-appele';

    try {
        $result = (new PopplerPdfThumbnailGenerator)->generate($pdf, storage_path('app/portability-out.jpg'), 400);
    } catch (Throwable) {
        $threw = true;
    } finally {
        @unlink($tmp);
        if ($hadTmp) {
            rename($backup, $tmp);
        }
        @unlink($pdf);
    }

    expect($threw)->toBeFalse()
        ->and($result)->toBeFalse();
});
