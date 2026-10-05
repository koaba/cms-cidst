<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class ColonnesRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'column_count' => 'required|integer|min:2|max:6',
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function casts(Request $request): array
    {
        // `integer` valide une chaîne numérique mais ne la caste pas :
        // sans ce cast, column_count serait stocké comme chaîne ("4").
        return ['column_count' => (int) $request->input('column_count')];
    }
}
