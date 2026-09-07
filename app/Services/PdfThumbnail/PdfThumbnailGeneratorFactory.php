<?php

namespace App\Services\PdfThumbnail;

use InvalidArgumentException;

/**
 * Instancie le bon driver de generation de miniature PDF selon la config.
 */
class PdfThumbnailGeneratorFactory
{
    public static function make(): PdfThumbnailGeneratorInterface
    {
        $driver = config('services.pdf_thumbnail.driver', 'poppler');

        return match ($driver) {
            'imagick' => new ImagickPdfThumbnailGenerator(),
            'poppler' => new PopplerPdfThumbnailGenerator(
                config('services.pdf_thumbnail.poppler_binary', 'pdftoppm')
            ),
            default => throw new InvalidArgumentException(
                "PdfThumbnailGeneratorFactory : driver \"{$driver}\" inconnu. Valeurs acceptees : \"imagick\", \"poppler\"."
            ),
        };
    }
}
