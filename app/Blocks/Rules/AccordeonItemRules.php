<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

/**
 * En-tête cliquable d'un item d'accordéon. Le contenu réel de l'item
 * est composé de ses propres enfants PageBlock, pas stocké ici.
 */
class AccordeonItemRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'title' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function casts(Request $request): array
    {
        return [];
    }
}
