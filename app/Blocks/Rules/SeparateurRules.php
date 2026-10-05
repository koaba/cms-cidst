<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class SeparateurRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'style' => 'nullable|in:fin,epais',
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
