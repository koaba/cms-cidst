<?php

namespace App\Services\PdfThumbnail;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Genere une miniature PDF via le binaire externe `pdftoppm` (paquet Poppler).
 * Aucune extension PHP requise. Driver recommande pour le dev local.
 */
class PopplerPdfThumbnailGenerator implements PdfThumbnailGeneratorInterface
{
    public function __construct(
        private readonly string $pdftoppmBinary = 'pdftoppm'
    ) {
    }

    public function generate(string $pdfPath, string $outputPath, int $width = 400): bool
    {
        if (!is_file($pdfPath)) {
            Log::warning("PopplerPdfThumbnailGenerator : fichier PDF introuvable ({$pdfPath}), generation annulee.");
            return false;
        }

        $tempPrefix = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdfthumb_' . uniqid();

        $process = new Process([
            $this->pdftoppmBinary,
            '-png',
            '-f', '1',
            '-l', '1',
            '-scale-to-x', (string) $width,
            '-scale-to-y', '-1',
            $pdfPath,
            $tempPrefix,
        ]);

        $process->setTimeout(30);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } catch (\Throwable $e) {
            Log::error("PopplerPdfThumbnailGenerator : echec de generation pour {$pdfPath}. " . $e->getMessage());
            return false;
        }

        $generated = $tempPrefix . '-1.png';

        if (!is_file($generated)) {
            Log::error("PopplerPdfThumbnailGenerator : fichier attendu introuvable apres execution ({$generated}).");
            return false;
        }

        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        if (!rename($generated, $outputPath)) {
            Log::error("PopplerPdfThumbnailGenerator : impossible de deplacer {$generated} vers {$outputPath}.");
            @unlink($generated);
            return false;
        }

        return true;
    }
}
