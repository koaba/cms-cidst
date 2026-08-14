<?php

namespace App\Contracts;

/**
 * Contrat pour tout modèle exposant une page publique (Article, Page,
 * futur Event...). Permet à SeoService de générer une URL canonique
 * sans connaître le nom de route de chaque modèle.
 */
interface HasPublicUrl
{
    public function publicUrl(): string;
}