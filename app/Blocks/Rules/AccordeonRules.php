<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

/**
 * Le bloc accordéon ne stocke qu'un titre optionnel : ses items sont de
 * vrais PageBlock enfants (type accordeon_item), pas du JSON imbriqué.
 */
class AccordeonRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'title' => 'nullable|string|max:255',
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