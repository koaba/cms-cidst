<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Services\Theme\ThemeRoles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Applique les donnees validees du formulaire des reglages : logo, theme,
 * code de suppression (hache) et filigranes par defaut.
 */
class SiteSettingService
{
    public function __construct(private ImageOptimizer $optimizer) {}

    private const BOOLEAN_FIELDS = [
        'video_watermark_default_enabled',
        'image_watermark_default_enabled',
        'pdf_watermark_default_enabled',
        'diaporama_watermark_default_enabled',
        'contact_images_enabled',
        'show_footer',
        'footer_show_contacts',
    ];

    public function update(SiteSetting $settings, array $data): SiteSetting
    {
        $logo = $data['logo'] ?? null;
        $deleteCode = $data['delete_code'] ?? null;
        unset($data['logo'], $data['delete_code']);

        if ($logo instanceof UploadedFile) {
            $data['logo_path'] = $this->replaceLogo($settings, $logo);
            $dimensions = MediaDimensionsReader::read($data['logo_path']);
            $data['logo_width'] = $dimensions['width'] ?? null;
            $data['logo_natural_height'] = $dimensions['height'] ?? null;
        }

        // Couleurs des sept roles : seuls les roles valides et remplis sont gardes, en majuscules ;
        // un champ absent ne change rien (la reinitialisation passe par resetColors()).
        if (is_array($data['theme_colors'] ?? null)) {
            $data['theme_colors'] = $this->normalizeColors($data['theme_colors']);
        } else {
            unset($data['theme_colors']);
        }

        // Un theme absent ou vide ne change rien (la colonne est NOT NULL).
        if (blank($data['theme'] ?? null)) {
            unset($data['theme']);
        }

        // Une valeur vide ne change rien (ces colonnes sont NOT NULL).
        foreach (['contact_images_max_mb', 'logo_height'] as $field) {
            if (blank($data[$field] ?? null)) {
                unset($data[$field]);
            }
        }

        if (filled($deleteCode)) {
            $settings->forceFill(['delete_code_hash' => Hash::make($deleteCode)]);
        }

        foreach (self::BOOLEAN_FIELDS as $field) {
            $data[$field] = filter_var($data[$field] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $settings->update($data);

        return $settings;
    }

    /** Retour aux couleurs par defaut : plus de couleurs personnalisees, reglages historiques remis a zero. */
    public function resetColors(SiteSetting $settings): SiteSetting
    {
        $defaults = ThemeRoles::defaults();

        $settings->update([
            'theme_colors' => null,
            'primary_color' => $defaults['primary'],
            'secondary_color' => $defaults['secondary'],
        ]);

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $colors
     * @return array<string, string>|null
     */
    private function normalizeColors(array $colors): ?array
    {
        $normalized = [];

        foreach (ThemeRoles::keys() as $role) {
            $value = $colors[$role] ?? null;

            if (is_string($value) && $value !== '') {
                $normalized[$role] = strtoupper($value);
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    private function replaceLogo(SiteSetting $settings, UploadedFile $logo): string
    {
        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        // JPEG et PNG deviennent du WebP (transparence conservee) : un logo charge sur chaque page.
        return $this->optimizer->optimize($logo->store('logos', 'public'));
    }
}
