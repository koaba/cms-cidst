<?php

namespace App\Concerns;

use App\Models\PageView;

/**
 * Ajoute le comptage de vues à tout modèle public (Article aujourd'hui,
 * Page ou autre demain). Complémentaire de HasSeo et HasPublicVisibility,
 * suit le même principe : un trait par responsabilité, combinable sur
 * n'importe quel modèle sans dupliquer de logique.
 */
trait HasPageViews
{
    public function pageViews()
    {
        return $this->morphMany(PageView::class, 'viewable');
    }

    public function viewsCount(): int
    {
        return $this->pageViews()->count();
    }
}
