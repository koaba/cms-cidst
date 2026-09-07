<?php

namespace App\Services\PdfThumbnail;

use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickException;

/**
 * Genere une miniature PDF via l'extension PHP Imagick (ImageMagick + Ghostscript).
 * Driver cible pour la production (typiquement Linux).
 */
class ImagickPdfThumbnailGenerator implements PdfThumbnailGeneratorInterface
{
    public function generate(string $pdfPath, string $outputPath, int $width = 400): bool
    {
        if (!is_file($pdfPath)) {
            Log::warning("ImagickPdfThumbnailGenerator : fichier PDF introuvable ({$pdfPath}), generation annulee.");
            return false;
        }

        if (!class_exists(Imagick::class)) {
            Log::error('ImagickPdfThumbnailGenerator : extension Imagick non disponible sur ce serveur.');
            return false;
        }

        $imagick = new Imagick();

        try {
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[0]');
            $imagick->setImageFormat('jpg');
            $imagick->setImageCompressionQuality(85);
            $imagick->thumbnailImage($width, 0);
            $imagick->flattenImages();

            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $imagick->writeImage($outputPath);
        } catch (ImagickException $e) {
            Log::error("ImagickPdfThumbnailGenerator : echec de generation pour {$pdfPath}. " . $e->getMessage());
            return false;
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }

        return true;
    }
}
