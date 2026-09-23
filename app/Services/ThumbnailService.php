<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ThumbnailService
{
    private const WIDTH = 400;
    private const HEIGHT = 225;

    /**
     * Génère une miniature redimensionnée pour un fichier déjà stocké sur le disque 'public'.
     * Retourne le chemin relatif de la miniature, ou null en cas d'échec (image non traitable, etc.).
     */
    public function generate(string $originalPath): ?string
    {
        $fullPath = Storage::disk('public')->path($originalPath);

        if (!file_exists($fullPath)) {
            return null;
        }

        try {
            $thumbnailPath = $this->thumbnailPathFor($originalPath);

            Storage::disk('public')->makeDirectory(dirname($thumbnailPath));

            $manager = ImageManager::usingDriver(Driver::class);
            $image = $manager->decode($fullPath);
            $image->cover(self::WIDTH, self::HEIGHT);
            $image->save(Storage::disk('public')->path($thumbnailPath), quality: 80);

            return $thumbnailPath;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function thumbnailPathFor(string $originalPath): string
    {
        $directory = pathinfo($originalPath, PATHINFO_DIRNAME);
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);

        return $directory . '/thumbnails/' . $filename . '.' . $extension;
    }
}