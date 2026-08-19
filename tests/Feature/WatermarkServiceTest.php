<?php

use App\Services\WatermarkService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    // Logo de filigrane : petit PNG transparent généré à la volée (pas de fixture binaire committée).
    $logo = imagecreatetruecolor(100, 50);
    imagesavealpha($logo, true);
    $transparent = imagecolorallocatealpha($logo, 0, 0, 0, 127);
    imagefill($logo, 0, 0, $transparent);
    imagefilledellipse($logo, 50, 25, 80, 40, imagecolorallocate($logo, 200, 30, 30));
    ob_start();
    imagepng($logo);
    $logoContent = ob_get_clean();
    imagedestroy($logo);

    Storage::disk('public')->put(config('watermark.logo_path'), $logoContent);
});

it('applique le filigrane sur une image et conserve ses dimensions', function () {
    $image = imagecreatetruecolor(400, 300);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    ob_start();
    imagejpeg($image);
    $imageContent = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('test/base.jpg', $imageContent);

    $result = app(WatermarkService::class)->watermarkImage('test/base.jpg');

    expect($result)->toBeTrue();

    $fullPath = Storage::disk('public')->path('test/base.jpg');
    [$width, $height] = getimagesize($fullPath);
    expect($width)->toBe(400)
        ->and($height)->toBe(300);
});

it('retourne false si le fichier image cible est introuvable', function () {
    $result = app(WatermarkService::class)->watermarkImage('test/does-not-exist.jpg');

    expect($result)->toBeFalse();
});

it('retourne false si le logo de filigrane est introuvable', function () {
    Storage::disk('public')->delete(config('watermark.logo_path'));

    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageContent = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('test/base2.jpg', $imageContent);

    $result = app(WatermarkService::class)->watermarkImage('test/base2.jpg');

    expect($result)->toBeFalse();
});

it('applique le filigrane sur un PDF et conserve le nombre de pages', function () {
    $fixture = new \TCPDF();
    $fixture->setPrintHeader(false);
    $fixture->setPrintFooter(false);
    $fixture->AddPage();
    $fixture->Write(0, 'Page de test');
    $pdfContent = $fixture->Output('', 'S');

    Storage::disk('public')->put('test/doc.pdf', $pdfContent);
    $originalSize = Storage::disk('public')->size('test/doc.pdf');

    $result = app(WatermarkService::class)->watermarkPdf('test/doc.pdf');

    expect($result)->toBeTrue();

    $fullPath = Storage::disk('public')->path('test/doc.pdf');

    // Réouverture avec FPDI pour confirmer que le fichier reste un PDF valide et exploitable
    $checker = new \setasign\Fpdi\Tcpdf\Fpdi();
    $pageCount = $checker->setSourceFile($fullPath);
    expect($pageCount)->toBe(1);

    // La superposition du filigrane modifie nécessairement la taille du fichier
    expect(Storage::disk('public')->size('test/doc.pdf'))->not->toBe($originalSize);
});

it('conserve le nombre de pages sur un PDF de plusieurs pages', function () {
    $fixture = new \TCPDF();
    $fixture->setPrintHeader(false);
    $fixture->setPrintFooter(false);
    $fixture->AddPage();
    $fixture->Write(0, 'Page 1');
    $fixture->AddPage();
    $fixture->Write(0, 'Page 2');
    $fixture->AddPage();
    $fixture->Write(0, 'Page 3');
    $pdfContent = $fixture->Output('', 'S');

    Storage::disk('public')->put('test/multi.pdf', $pdfContent);

    $result = app(WatermarkService::class)->watermarkPdf('test/multi.pdf');
    expect($result)->toBeTrue();

    $checker = new \setasign\Fpdi\Tcpdf\Fpdi();
    $pageCount = $checker->setSourceFile(Storage::disk('public')->path('test/multi.pdf'));
    expect($pageCount)->toBe(3);
});

it('retourne false si le fichier PDF cible est introuvable', function () {
    $result = app(WatermarkService::class)->watermarkPdf('test/does-not-exist.pdf');

    expect($result)->toBeFalse();
});