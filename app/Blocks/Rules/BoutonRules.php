<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class BoutonRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'label' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'style' => 'nullable|in:primaire,secondaire,outline',
            'new_tab' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function casts(Request $request): array
    {
        return ['new_tab' => $request->boolean('new_tab')];
    }
}