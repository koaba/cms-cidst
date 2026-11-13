<?php

namespace App\Http\Requests\Admin;

use App\Services\Theme\ContrastGuard;
use App\Services\Theme\ThemePalette;
use App\Services\Theme\ThemeRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valide le formulaire des reglages du site (admin/settings).
 */
class UpdateSiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        // Un champ par role ; un role inconnu (ou le texte sur primaire, jamais saisi) est refuse.
        $colorRules = ['theme_colors' => ['nullable', 'array:'.implode(',', ThemeRoles::keys())]];

        foreach (ThemeRoles::keys() as $role) {
            $colorRules['theme_colors.'.$role] = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        return [
            ...$colorRules,
            'hero_eyebrow' => 'nullable|string|max:255',
            'hero_eyebrow_size' => 'nullable|in:xs,sm,base,lg,xl',
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // Le menu public fait 80 px de haut : 72 px au plus pour garder une marge.
            'logo_height' => 'nullable|integer|min:24|max:72',
            'cta_primary_label' => 'nullable|string|max:100',
            'cta_primary_target' => 'nullable|string|max:255',
            'cta_secondary_label' => 'nullable|string|max:100',
            'cta_secondary_target' => 'nullable|string|max:255',
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme' => ['nullable', Rule::in(array_keys(config('themes.themes')))],
            'news_ticker_direction' => 'nullable|in:horizontal,vertical',
            'pages_grid_columns' => 'nullable|in:2,3,4',
            'pages_image_size' => 'nullable|in:small,medium,large',
            'facebook_url' => 'nullable|url|max:255',
            'pdf_documents_title' => 'nullable|string|max:255',
            'pdf_documents_content' => 'nullable|string|max:2000',
            'video_watermark_default_enabled' => 'nullable|boolean',
            'image_watermark_default_enabled' => 'nullable|boolean',
            'pdf_watermark_default_enabled' => 'nullable|boolean',
            'diaporama_watermark_default_enabled' => 'nullable|boolean',
            'contact_images_enabled' => 'nullable|boolean',
            'contact_images_max_mb' => 'nullable|integer|min:1|max:10',
            'contact_public_email' => 'nullable|email|max:255',
            'show_footer' => 'nullable|boolean',
            'footer_description' => 'nullable|string|max:255',
            'footer_show_contacts' => 'nullable|boolean',
            'footer_copyright' => 'nullable|string|max:255',
            'delete_code' => 'nullable|string|min:8|max:50',
        ];
    }

    /**
     * Garde-fou de contraste (politique A) : le texte principal sous 4,5 sur le fond ou sur la
     * surface bloque l'enregistrement. Les autres écarts sont signalés après l'enregistrement.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $colors = $this->input('theme_colors');

            if (! is_array($colors) || collect($validator->errors()->keys())->contains(fn (string $key): bool => str_starts_with($key, 'theme_colors'))) {
                return;
            }

            foreach ((new ContrastGuard)->blockers(ThemePalette::resolve($colors)) as $message) {
                $validator->errors()->add('theme_colors.text', $message);
            }
        }];
    }
}
