<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageVariantService
{
    public const WIDTHS = [480, 768, 1280];

    /**
     * Genere les variantes WebP d'une image du disque 'public'.
     * Une largeur superieure ou egale a l'original est ignoree (jamais d'agrandissement).
     * Retourne [largeur => chemin relatif], ou [] en cas d'echec.
     *
     * @return array<int, string>
     */
    public function generate(string $originalPath): array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($originalPath)) {
            return [];
        }

        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $originalWidth = $manager->decode($disk->path($originalPath))->width();
            $variants = [];

            foreach (self::WIDTHS as $width) {
                if ($width >= $originalWidth) {
                    continue;
                }

                $variantPath = $this->variantPathFor($originalPath, $width);
                $disk->makeDirectory(dirname($variantPath));

                $image = $manager->decode($disk->path($originalPath));
                $image->scaleDown(width: $width);
                $image->save($disk->path($variantPath), quality: 80);

                $variants[$width] = $variantPath;
            }

            return $variants;
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    private function variantPathFor(string $originalPath, int $width): string
    {
        $directory = pathinfo($originalPath, PATHINFO_DIRNAME);
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);

        return $directory.'/variants/'.$filename.'-'.$width.'.webp';
    }
}
