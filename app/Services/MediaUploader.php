<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class MediaUploader
{
    /**
     * Stocke un fichier envoye sur le disque public et cree le Media correspondant.
     *
     * Les attributs fournis completent ceux calcules ici (chemin, nom, type MIME,
     * taille) : ils ne les remplacent jamais.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function fromUpload(UploadedFile $file, string $directory, array $attributes = []): Media
    {
        $computed = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];

        $path = $file->store($directory, 'public')
            ?: throw new RuntimeException("Impossible d'enregistrer le fichier dans « {$directory} ».");

        return Media::create(['path' => $path] + $computed + $attributes);
    }
}
