<?php

namespace App\Services\PdfThumbnail;

/**
 * Generateur nul : ne produit aucune miniature serveur.
 * Utilise quand aucun outil (Poppler, Imagick) n'est disponible, ou en repli
 * quand le driver configure est inconnu. L'application reste fonctionnelle :
 * la miniature client (pdf.js) sert alors de seule source.
 */
class NullPdfThumbnailGenerator implements PdfThumbnailGeneratorInterface
{
    public function generate(string $pdfPath, string $outputPath, int $width = 400): bool
    {
        return false;
    }
}
