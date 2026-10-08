<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

final class MediaDimensionsReader
{
    /**
     * Lit les dimensions d'une image du disque public.
     * Retourne [] si le fichier est absent ou illisible (jamais d'exception).
     *
     * @return array{width: int, height: int}|array{}
     */
    public static function read(string $path): array
    {
        $size = rescue(fn () => getimagesize(Storage::disk('public')->path($path)), false, false);

        return $size ? ['width' => $size[0], 'height' => $size[1]] : [];
    }
}
