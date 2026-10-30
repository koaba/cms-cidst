<?php

namespace App\Observers;

use App\Support\PublicPageCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalide le cache de rendu des pages publiques des qu'un modele qui l'alimente
 * change : Page, PageBlock, SeoMeta, Media (images et PDF), PdfDocument.
 */
class PublicPageCacheObserver
{
    public function saved(Model $model): void
    {
        PublicPageCache::forgetAll();
    }

    public function deleted(Model $model): void
    {
        PublicPageCache::forgetAll();
    }
}
