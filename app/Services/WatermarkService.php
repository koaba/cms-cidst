<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Service centralisé de filigrane (logo semi-transparent), appliqué en place
 * sur un fichier déjà stocké sur le disque 'public'. Paramètres (position,
 * opacité, taille, logo) lus depuis config/watermark.php.
 *
 * Utilisé par ArticleController (PDF joints + galerie d'images) et par
 * SliderController (diaporamas) — logique volontairement centralisée ici
 * plutôt que dupliquée par contrôleur, pour rester cohérent si les
 * paramètres deviennent configurables depuis le dashboard plus tard.
 */
class WatermarkService
{
    /**
     * Applique le filigrane sur une image déjà stockée sur le disque 'public'.
     * Écrase le fichier original en place. Ne lève jamais d'exception :
     * retourne false en cas d'échec (fichier manquant, image corrompue, etc.),
     * pour que l'appelant puisse continuer sans bloquer l'upload.
     */
    public function watermarkImage(string $relativePath): bool
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($relativePath);
        $logoFullPath = $disk->path(config('watermark.logo_path'));

        if (! file_exists($fullPath) || ! file_exists($logoFullPath)) {
            report(new \RuntimeException("Watermark: fichier ou logo introuvable ({$relativePath})"));

            return false;
        }

        try {
            $manager = ImageManager::usingDriver(Driver::class);
                      $image = $manager->decode($fullPath);
            $logo = $manager->decode($logoFullPath);

            $targetWidth = max((int) round($image->width() * (config('watermark.size_percent', 10) / 100)), 1);
            $logo->scale(width: $targetWidth);

            $marginPx = (int) round($image->width() * (config('watermark.margin_percent', 3) / 100));

            $image->insert(
                $logo,
                x: $marginPx,
                y: $marginPx,
                alignment: $this->mapImageAlignment(config('watermark.position', 'br')),
              transparency: ((int) config('watermark.opacity', 15)) / 100
            );

            $image->save($fullPath);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Applique le filigrane sur chaque page d'un PDF déjà stocké sur le disque 'public'.
     * Écrase le fichier original en place. Ne lève jamais d'exception :
     * retourne false en cas d'échec.
     */
    public function watermarkPdf(string $relativePath): bool
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($relativePath);
        $logoFullPath = $disk->path(config('watermark.logo_path'));

        if (! file_exists($fullPath) || ! file_exists($logoFullPath)) {
            report(new \RuntimeException("Watermark: fichier ou logo introuvable ({$relativePath})"));

            return false;
        }

        $logoSize = @getimagesize($logoFullPath);
        $logoRatio = ($logoSize && $logoSize[1] > 0) ? $logoSize[0] / $logoSize[1] : 1;

        $tmpPath = tempnam(sys_get_temp_dir(), 'wm_pdf_');

        try {
            $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            // Indispensable : sans ça, TCPDF insère une page vide supplémentaire dès que
            // le filigrane (volontairement proche du bord bas) déclenche son détecteur
            // de saut de page automatique. Chaque page source se retrouvait donc doublée.
            $pdf->SetAutoPageBreak(false, 0);

            $pageCount = $pdf->setSourceFile($fullPath);

            $sizePercent = config('watermark.size_percent', 7);
            $marginPercent = config('watermark.margin_percent', 3);
            $opacity = config('watermark.opacity', 15);
            $position = config('watermark.position', 'br');

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $wmWidth = $size['width'] * ($sizePercent / 100);
                $wmHeight = $wmWidth / $logoRatio;
                $margin = $size['width'] * ($marginPercent / 100);

                [$x, $y] = $this->pdfPosition($position, $size['width'], $size['height'], $wmWidth, $wmHeight, $margin);

                $pdf->SetAlpha($opacity / 100);
                $pdf->Image($logoFullPath, $x, $y, $wmWidth, $wmHeight, '', '', '', false, 300);
                $pdf->SetAlpha(1);
            }

            $pdf->Output($tmpPath, 'F');
            copy($tmpPath, $fullPath);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    private function mapImageAlignment(string $position): Alignment
    {
        return match ($position) {
            'br' => Alignment::BOTTOM_RIGHT,
            'bl' => Alignment::BOTTOM_LEFT,
            'tr' => Alignment::TOP_RIGHT,
            'tl' => Alignment::TOP_LEFT,
            default => Alignment::BOTTOM_RIGHT,
        };
    }

    private function pdfPosition(string $position, float $pageWidth, float $pageHeight, float $wmWidth, float $wmHeight, float $margin): array
    {
        return match ($position) {
            'br' => [$pageWidth - $wmWidth - $margin, $pageHeight - $wmHeight - $margin],
            'bl' => [$margin, $pageHeight - $wmHeight - $margin],
            'tr' => [$pageWidth - $wmWidth - $margin, $margin],
            'tl' => [$margin, $margin],
            default => [$pageWidth - $wmWidth - $margin, $pageHeight - $wmHeight - $margin],
        };
    }
}