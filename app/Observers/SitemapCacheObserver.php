<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Invalide le cache du sitemap dès qu'un contenu public change.
 */
class SitemapCacheObserver
{
    public function saved(Model $model): void
    {
        Cache::forget('sitemap.xml');
    }

    public function deleted(Model $model): void
    {
        Cache::forget('sitemap.xml');
    }
}
