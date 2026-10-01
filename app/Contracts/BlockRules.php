<?php

namespace App\Contracts;

use App\Models\PageBlock;
use Illuminate\Http\Request;

/**
 * Contrat de validation d'un type de bloc. Une classe par type : le
 * contrôleur n'a plus à connaître les règles, il délègue. Prépare le
 * registre de types (un nouveau type = un nouveau fichier).
 */
interface BlockRules
{
    /**
     * Règles de validation. $block est fourni en mise à jour uniquement,
     * pour les règles qui dépendent de l'état existant (ex. média déjà
     * attaché).
     */
    public function rules(bool $isCreate, ?PageBlock $block = null): array;

    /** Messages personnalisés (clé de règle => message). */
    public function messages(): array;

    /**
     * Valeurs castées à fusionner après validation : `integer` et `boolean`
     * valident mais ne castent pas.
     */
    public function casts(Request $request): array;
}