<?php

namespace App\Services\PdfThumbnail;

use Illuminate\Support\Facades\Log;

/**
 * Instancie le bon driver de generation de miniature PDF selon la config.
 */
class PdfThumbnailGeneratorFactory
{
    public static function make(): PdfThumbnailGeneratorInterface
    {
        $driver = config('services.pdf_thumbnail.driver', 'poppler');

        return match ($driver) {
            'imagick' => new ImagickPdfThumbnailGenerator,
            'poppler' => new PopplerPdfThumbnailGenerator(
                config('services.pdf_thumbnail.poppler_binary', 'pdftoppm')
            ),
            'none' => new NullPdfThumbnailGenerator,
            default => self::fallback($driver),
        };
    }

    private static function fallback(mixed $driver): PdfThumbnailGeneratorInterface
    {
        Log::warning('PdfThumbnailGeneratorFactory : driver "'.(is_scalar($driver) ? $driver : gettype($driver)).'" inconnu, miniature serveur desactivee. Valeurs acceptees : "imagick", "poppler", "none".');

        return new NullPdfThumbnailGenerator;
    }
}
