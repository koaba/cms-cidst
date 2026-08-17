<?php

namespace App\Traits;

use App\Models\Media;

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

    public function attachUploadedFiles(array $files, string $storagePath): void
    {
        $order = $this->media()->count();
        foreach ($files as $file) {
            $path = $file->store($storagePath, 'public');
            $media = Media::create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
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