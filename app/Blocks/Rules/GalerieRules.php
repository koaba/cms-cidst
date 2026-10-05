<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class GalerieRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'layout' => 'required|in:grid,carousel',
            'images' => ($isCreate ? 'required' : 'nullable').'|array|min:1|max:20',
            'images.*' => 'image|max:5120',
            'images_alt' => 'nullable|array',
            'images_alt.*' => 'nullable|string|max:255',
            'images_caption' => 'nullable|array',
            'images_caption.*' => 'nullable|string|max:255',
            'delete_media' => 'nullable|array',
            'delete_media.*' => 'integer',
            'apply_watermark' => 'nullable|boolean',
            'autoplay' => 'nullable|boolean',
            'autoplay_interval' => 'nullable|integer|min:2|max:30',
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function casts(Request $request): array
    {
        // `integer` et `boolean` valident mais ne castent pas : casts explicites.
        return [
            'autoplay' => $request->boolean('autoplay'),
            'autoplay_interval' => (int) $request->input('autoplay_interval', 4),
        ];
    }
}