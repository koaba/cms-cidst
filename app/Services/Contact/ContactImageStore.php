<?php

namespace App\Services\Contact;

use App\Models\ContactAttachment;
use App\Models\ContactMessage;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reencode une image envoyee par un visiteur (GD) et la range sur le disque prive.
 * Le fichier d'origine n'est jamais conserve : seuls les pixels survivent
 * (code cache, commentaires et donnees EXIF disparaissent).
 */
class ContactImageStore
{
    public const DISK = 'local';

    private const EXTENSIONS = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    /** Retourne null si le fichier n'est pas une image saine (rien n'est alors enregistre). */
    public function store(string $path, string $originalName, ContactMessage $message): ?ContactAttachment
    {
        $info = @getimagesize($path);
        $side = (int) config('contact.max_side');

        if ($info === false || ! isset(self::EXTENSIONS[$info[2]]) || $info[0] > $side || $info[1] > $side) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            default => @imagecreatefromwebp($path),
        };

        if (! $image instanceof GdImage) {
            return null;
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $image = $this->orient($image, $path);
        } else {
            imagesavealpha($image, true);
        }

        ob_start();
        match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($image, null, (int) config('contact.jpeg_quality')),
            IMAGETYPE_PNG => imagepng($image, null, 6),
            default => imagewebp($image, null, (int) config('contact.jpeg_quality')),
        };
        $binary = (string) ob_get_clean();

        if ($binary === '') {
            return null;
        }

        $target = 'contact/'.Str::uuid().'.'.self::EXTENSIONS[$info[2]];
        Storage::disk(self::DISK)->put($target, $binary);

        return $message->attachments()->create([
            'path' => $target,
            'original_name' => $this->cleanName($originalName),
            'mime' => (string) image_type_to_mime_type($info[2]),
            'size' => strlen($binary),
        ]);
    }

    private function orient(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    private function cleanName(string $name): string
    {
        $clean = trim((string) preg_replace('/[^\p{L}\p{N}._ -]/u', '', basename($name)));

        return Str::limit($clean === '' ? 'image' : $clean, 100, '');
    }
}
