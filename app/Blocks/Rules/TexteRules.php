<?php

namespace App\Blocks\Rules;

use App\Blocks\Rules\Concerns\HasDesignRules;
use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class TexteRules implements BlockRules
{
    use HasDesignRules;

    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
        ] + $this->designRules();
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