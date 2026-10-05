<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class SectionFondRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'text' => 'required|string',
            'bg_color' => 'nullable|in:gray,blue,dark',
            'button_label' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'button_new_tab' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function casts(Request $request): array
    {
        return ['button_new_tab' => $request->boolean('button_new_tab')];
    }
}