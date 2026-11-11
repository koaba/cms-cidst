<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Convertit en WebP une image JPEG ou PNG deja stockee sur le disque 'public'.
 *
 * L'original n'est pas conserve : le but est d'alleger le site. Le WebP n'est
 * garde que s'il est plus leger ; sinon le fichier reste tel quel. GIF (animations)
 * et WebP ne sont jamais touches. Ne leve jamais d'exception.
 */
class ImageOptimizer
{
    // 4 octets par pixel en memoire GD, doubles : copie lors de la rotation EXIF.
    private const BYTES_PER_PIXEL = 8;

    /**
     * Retourne le chemin final : le nouveau chemin en .webp si la conversion a eu lieu,
     * sinon le chemin recu.
     */
    public function optimize(string $path): string
    {
        if (! config('media.webp.enabled')) {
            return $path;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $path;
        }

        $binary = $this->encode($disk->path($path), (int) config('media.webp.quality', 82));

        if ($binary === null || strlen($binary) >= $disk->size($path)) {
            return $path;
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $target = ($directory === '.' ? '' : $directory.'/').pathinfo($path, PATHINFO_FILENAME).'.webp';

        $disk->put($target, $binary);
        $disk->delete($path);

        return $target;
    }

    /** Le contenu WebP, ou null si le fichier n'est pas un JPEG ou PNG sain (ou si la memoire manque). */
    protected function encode(string $file, int $quality): ?string
    {
        $info = @getimagesize($file);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return null;
        }

        // Une erreur fatale de memoire ne se rattrape pas : on garde l'original plutot que de decoder.
        if ($this->availableMemory() < $info[0] * $info[1] * self::BYTES_PER_PIXEL) {
            Log::warning('media.webp_skipped', ['reason' => 'not_enough_memory', 'file' => basename($file)]);

            return null;
        }

        try {
            $image = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($file) : @imagecreatefrompng($file);

            if (! $image instanceof GdImage) {
                return null;
            }

            if ($info[2] === IMAGETYPE_JPEG) {
                $image = $this->orient($image, $file);
            } else {
                imagepalettetotruecolor($image);
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            ob_start();
            imagewebp($image, null, $quality);
            $binary = (string) ob_get_clean();

            return $binary === '' ? null : $binary;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Memoire encore utilisable par PHP, en octets. */
    protected function availableMemory(): int
    {
        $raw = trim((string) ini_get('memory_limit'));
        $value = (int) $raw;

        if ($value <= 0) {
            return PHP_INT_MAX;
        }

        $bytes = match (strtolower(substr($raw, -1))) {
            'g' => $value * 1024 ** 3,
            'm' => $value * 1024 ** 2,
            'k' => $value * 1024,
            default => $value,
        };

        return max(0, $bytes - memory_get_usage(true));
    }

    /** Le WebP ne porte pas l'EXIF : l'orientation doit etre appliquee aux pixels. */
    private function orient(GdImage $image, string $file): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}
