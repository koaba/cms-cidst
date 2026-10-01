<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class CitationRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'content' => 'required|string',
            'author' => 'nullable|string|max:255',
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