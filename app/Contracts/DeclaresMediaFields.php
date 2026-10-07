<?php

namespace App\Contracts;

/**
 * Optionnel : implémenté par les types de blocs qui reçoivent des fichiers
 * ou des médias. Déclare les clés de formulaire à retirer des données
 * validées avant stockage dans la colonne JSON `data` (elles sont traitées
 * par BlockMediaService). Un type sans média n'implémente rien.
 */
interface DeclaresMediaFields
{
    /**
     * @return list<string>
     */
    public function mediaFields(): array;
}
