<?php

namespace App\Services\Contact;

use App\Models\ContactAttachment;
use App\Models\ContactMessage;
use GdImage;
use Illuminate\Support\Facades\Log;
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

    // 4 octets par pixel en memoire GD, doubles : copie lors de la rotation EXIF.
    private const BYTES_PER_PIXEL = 8;

    /** Retourne null si le fichier n'est pas une image saine (rien n'est alors enregistre, un avertissement est journalise). */
    public function store(string $path, string $originalName, ContactMessage $message): ?ContactAttachment
    {
        $info = @getimagesize($path);
        $side = (int) config('contact.max_side');

        if ($info === false || ! isset(self::EXTENSIONS[$info[2]]) || $info[0] > $side || $info[1] > $side) {
            return $this->discard($message, 'not_a_safe_image');
        }

        // Une erreur fatale de memoire ne se rattrape pas : on refuse avant de decoder.
        if ($this->availableMemory() < $info[0] * $info[1] * self::BYTES_PER_PIXEL) {
            return $this->discard($message, 'not_enough_memory');
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            default => @imagecreatefromwebp($path),
        };

        if (! $image instanceof GdImage) {
            return $this->discard($message, 'decode_failed');
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
            return $this->discard($message, 'encode_failed');
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

    private function discard(ContactMessage $message, string $reason): null
    {
        Log::warning('contact.image_discarded', ['contact_message_id' => $message->id, 'reason' => $reason]);

        return null;
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
