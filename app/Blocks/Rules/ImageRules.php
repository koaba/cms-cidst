<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Contracts\DeclaresMediaFields;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class ImageRules implements BlockRules, DeclaresMediaFields
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'image' => ($isCreate ? 'required' : 'nullable').'|image|max:5120',
            'alt' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:255',
            'delete_image' => 'nullable|boolean',
            'apply_watermark' => 'nullable|boolean',
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

    public function mediaFields(): array
    {
        return ['image', 'delete_image', 'apply_watermark'];
    }
}
