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
    /**
     * Attache les médias existants dont l'id est fourni, en ignorant
     * ceux déjà attachés (idempotent), et en poursuivant l'ordre à
     * partir du nombre de médias déjà présents.
     *
     * @param  int[]  $mediaIds
     */
    protected function attachExistingMedia(array $mediaIds): void
    {
        $order = $this->media()->count();

        foreach ($mediaIds as $mediaId) {
            if (! $this->media()->where('media.id', $mediaId)->exists()) {
                $this->media()->attach($mediaId, ['order' => $order++]);
            }
        }
    }

    /**
     * Crée un enregistrement Media pour chaque fichier uploadé, le stocke
     * sur le disque public, et l'attache en poursuivant l'ordre existant.
     *
     * @param  \Illuminate\Http\UploadedFile[]  $files
     */
    protected function attachUploadedFiles(array $files, string $storagePath): void
    {
        $order = $this->media()->count();

        foreach ($files as $file) {
            $media = Media::create([
                'path' => $file->store($storagePath, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            $this->media()->attach($media->id, ['order' => $order++]);
        }
    }

    /**
     * Détache les médias dont l'id est fourni ET réellement possédés par
     * ce modèle (scope de sécurité — ignore silencieusement tout id qui
     * n'appartient pas à ce modèle), puis supprime réellement (ligne DB +
     * fichier physique) ceux qui ne sont plus référencés ailleurs.
     *
     * @param  int[]  $mediaIds
     */
    protected function detachOwnedMedia(array $mediaIds): void
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