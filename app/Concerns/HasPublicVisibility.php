<?php

namespace App\Concerns;

/**
 * Fournit un scope `publiclyVisible()` réutilisable par tout modèle
 * exposant du contenu public (Article, Page, futur Event...).
 *
 * - Tout modèle doit avoir une colonne `is_published` (bool).
 * - Si le modèle gère en plus `published_at` (publication programmée),
 *   le scope exclut automatiquement les contenus dont la date de
 *   publication n'est pas encore atteinte.
 *
 * Aucune configuration requise : la détection se fait via $fillable,
 * donc un futur modèle avec `published_at` dans son $fillable héritera
 * du comportement "publication programmée" sans rien ajouter ici.
 */
trait HasPublicVisibility
{
    public function scopePubliclyVisible($query)
    {
        $query->where('is_published', true);

        if ($this->supportsScheduledPublishing()) {
            $query->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
        }

        return $query;
    }

    /**
     * Équivalent du scope publiclyVisible(), mais pour une instance déjà
     * chargée (ex. via route model binding) plutôt que pour une requête.
     * Utile dans les méthodes show() où le modèle est résolu avant même
     * qu'on sache s'il doit être visible publiquement.
     */
    public function isPubliclyVisible(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        if (! $this->supportsScheduledPublishing() || $this->published_at === null) {
            return true;
        }

        return $this->published_at->lessThanOrEqualTo(now());
    }

    protected function supportsScheduledPublishing(): bool
    {
        return in_array('published_at', $this->getFillable(), true);
    }
}