<?php

namespace App\Blocks\Rules;

use App\Contracts\BlockRules;
use App\Models\PageBlock;
use Illuminate\Http\Request;

class BanniereHeroRules implements BlockRules
{
    public function rules(bool $isCreate, ?PageBlock $block = null): array
    {
        return [
            'titre' => 'required|string|max:255',
            'sous_titre' => 'nullable|string|max:500',
            'bouton_texte' => 'nullable|string|max:100',
            'bouton_url' => 'nullable|required_with:bouton_texte|url|max:255',
            'overlay_opacity' => 'nullable|integer|min:0|max:100',
            'image' => [
                $isCreate ? 'required' : 'nullable',
                'required_if:delete_image,1',
                'image',
                'max:5120',
            ],
            'alt' => 'nullable|string|max:255',
            'delete_image' => 'nullable|boolean',
            'apply_watermark' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required_if' => 'Impossible de retirer l\'image sans en fournir une nouvelle : la bannière hero doit toujours avoir une image de fond.',
        ];
    }

    public function casts(Request $request): array
    {
        // `integer` valide mais ne caste pas : cast explicite indispensable.
        return ['overlay_opacity' => (int) $request->input('overlay_opacity', 40)];
    }
}
