<?php

namespace App\Services\PdfThumbnail;

interface PdfThumbnailGeneratorInterface
{
    /**
     * Genere une miniature (image) a partir de la premiere page d'un PDF.
     *
     * @param string $pdfPath    Chemin absolu du fichier PDF source (deja filigrane si applicable).
     * @param string $outputPath Chemin absolu du fichier image de destination (ex: .../thumb.jpg).
     * @param int    $width      Largeur cible de la miniature en pixels (hauteur calculee au prorata).
     *
     * @return bool true si la miniature a ete generee avec succes, false sinon.
     */
    public function generate(string $pdfPath, string $outputPath, int $width = 400): bool;
}
