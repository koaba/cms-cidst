<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
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
            'delete_code' => 'nullable|string|min:8|max:50',
        ];
    }
}
