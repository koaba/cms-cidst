<?php

namespace App\Traits;

use App\Models\Media;
use Closure;
use Illuminate\Support\Facades\Storage;

/**
 * Factorise les opérations communes sur une collection de médias ordonnée
 * (attacher des médias existants, uploader de nouveaux fichiers, détacher
 * en nettoyant les orphelins) pour tout modèle exposant une relation
 * media() de type morphToMany(Media::class, 'mediable')->withPivot('order').
 *
 * Utilisable par Article (galerie), Diaporama, Slider — et tout futur
 * modèle exposant le même contrat, sans dupliquer la logique dans
 * chaque contrôleur.
 *
 * Complémentaire de HasOrphanMediaCleanup : celui-ci détache TOUT
 * (utilisé à la suppression d'un modèle entier), alors que ce trait
 * gère les opérations partielles typiques d'un formulaire d'édition
 * (ajouter certains médias, en retirer d'autres, dans le même appel).
 */
trait HasOrderedMediaCollection
{
    public function attachExistingMedia(array $mediaIds): void
    {
        $order = $this->media()->count();
        foreach ($mediaIds as $mediaId) {
            if (! $this->media()->where('media.id', $mediaId)->exists()) {
                $this->media()->attach($mediaId, ['order' => $order++]);
            }
        }
    }

   /**
 * @param  Closure(string $path): void|null  $beforeCreate  Hook optionnel exécuté sur le
 *         fichier déjà stocké, avant la création du Media (ex: filigrane). Le hook travaille
 *         en place sur le disque 'public' ; la taille est recalculée après son exécution pour
 *         refléter le fichier final (un filigrane peut changer la taille du fichier).
 * @param  array  $thumbnails  Tableau optionnel de miniatures alignées par nom de fichier
 *         original, au format [['name' => string, 'thumbnail' => string|null dataURL base64], ...].
 *         Utilisé notamment pour les miniatures PDF générées côté navigateur (pdf.js).
 */
public function attachUploadedFiles(array $files, string $storagePath, ?Closure $beforeCreate = null, array $thumbnails = []): void
{
    $order = $this->media()->count();

    $thumbnailsByName = collect($thumbnails)->keyBy('name');

    foreach ($files as $file) {
        $path = $file->store($storagePath, 'public');
        $size = $file->getSize();
        if ($beforeCreate !== null) {
            $beforeCreate($path);
            $size = Storage::disk('public')->size($path);
        }

        $thumbnailPath = null;
        $thumbnailData = $thumbnailsByName->get($file->getClientOriginalName())['thumbnail'] ?? null;
        if ($thumbnailData && preg_match('/^data:image\/(\w+);base64,(.+)$/', $thumbnailData, $matches)) {
            $extension = $matches[1];
            $binaryData = base64_decode($matches[2]);
            $thumbnailPath = $storagePath.'/thumbnails/'.uniqid('pdf_', true).'.'.$extension;
            Storage::disk('public')->put($thumbnailPath, $binaryData);
        }

        $media = Media::create([
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $file->getClientMimeType(),
            'size' => $size,
        ]);
        $this->media()->attach($media->id, ['order' => $order++]);
    }
}

    public function detachOwnedMedia(array $mediaIds): void
    {
        $ownedIds = $this->media()->pluck('media.id')->all();
        foreach (array_intersect($mediaIds, $ownedIds) as $mediaId) {
            $this->media()->detach($mediaId);
            $media = Media::find($mediaId);
            if ($media && $media->mediables()->count() === 0) {
                $media->delete();
            }
        }
    }
}