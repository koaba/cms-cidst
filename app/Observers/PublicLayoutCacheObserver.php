<?php

namespace App\Observers;

use App\Support\PublicLayout;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalide le cache du layout public (menu, ticker, sidebar) des qu'un modele
 * qui l'alimente change : Menu, NewsTicker, Article, Media (couverture de la sidebar).
 */
class PublicLayoutCacheObserver
{
    public function saved(Model $model): void
    {
        PublicLayout::forget();
    }

    public function deleted(Model $model): void
    {
        PublicLayout::forget();
    }
}
