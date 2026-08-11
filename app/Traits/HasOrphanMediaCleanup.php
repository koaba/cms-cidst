<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Detache tous les medias lies a un modele, et supprime reellement
 * (ligne DB + fichier physique, via l'event `deleting` du modele Media)
 * ceux qui ne sont plus references ailleurs.
 *
 * Utilisable par tout modele exposant une relation media() de type
 * morphToMany(Media::class, 'mediable') - Page, Slider, Article...
 */
trait HasOrphanMediaCleanup
{
    protected function detachAndPruneOrphanMedia(Model $model): void
    {
        $mediaItems = $model->media()->get();

        $model->media()->detach();

        foreach ($mediaItems as $media) {
            if ($media->mediables()->count() === 0) {
                $media->delete();
            }
        }
    }
}