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

        $isolatedTempDir = storage_path('app/tmp/pdfthumb_' . uniqid());
        mkdir($isolatedTempDir, 0755, true);

        $tempPrefix = $isolatedTempDir . DIRECTORY_SEPARATOR . 'thumb';

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
        $process->setEnv(['TMP' => $isolatedTempDir, 'TEMP' => $isolatedTempDir]);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } catch (\Throwable $e) {
            Log::error("PopplerPdfThumbnailGenerator : echec de generation pour {$pdfPath}. " . $e->getMessage());
            $this->cleanupTempDir($isolatedTempDir);
            return false;
        }

        $matches = glob($tempPrefix . '-*.{png,PNG}', GLOB_BRACE);

if (empty($matches)) {
    Log::error("PopplerPdfThumbnailGenerator : fichier attendu introuvable apres execution (motif {$tempPrefix}-*.png).");
    $this->cleanupTempDir($isolatedTempDir);
    return false;
}

$generated = $matches[0];

       

        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $success = rename($generated, $outputPath);

        if (!$success) {
            Log::error("PopplerPdfThumbnailGenerator : impossible de deplacer {$generated} vers {$outputPath}.");
        }

        $this->cleanupTempDir($isolatedTempDir);

        return $success;
    }

       private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}