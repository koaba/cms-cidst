<?php

namespace App\Services;

use App\Models\Media;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MediaUploader
{
    /**
     * Stocke un fichier envoye sur le disque public et cree le Media correspondant.
     *
     * Les attributs fournis completent ceux calcules ici (chemin, nom, type MIME,
     * taille) : ils ne les remplacent jamais.
     *
     * @param  array<string, mixed>  $attributes
     * @param  Closure(string $path): mixed|null  $beforeCreate  Hook execute sur le fichier deja
     *                                                           stocke, avant la creation du Media (ex : filigrane). La taille est relue
     *                                                           apres son execution. S'il retourne un tableau, celui-ci complete les attributs
     *                                                           calcules (ex : dimensions lues apres filigrane). Si le hook leve une exception,
     *                                                           le fichier stocke est supprime puis l'exception est relancee.
     */
    public function fromUpload(UploadedFile $file, string $directory, array $attributes = [], ?Closure $beforeCreate = null): Media
    {
        $computed = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];

        $path = $file->store($directory, 'public')
            ?: throw new RuntimeException("Impossible d'enregistrer le fichier dans « {$directory} ».");

        $fromHook = [];

        if ($beforeCreate !== null) {
            try {
                $result = $beforeCreate($path);
            } catch (Throwable $e) {
                Storage::disk('public')->delete($path);

                throw $e;
            }

            $computed['size'] = Storage::disk('public')->size($path);
            $fromHook = is_array($result) ? $result : [];
        }

        return Media::create(['path' => $path] + $computed + $fromHook + $attributes);
    }
}
