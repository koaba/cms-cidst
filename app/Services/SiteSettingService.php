<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Applique les donnees validees du formulaire des reglages : logo, theme,
 * code de suppression (hache) et filigranes par defaut.
 */
class SiteSettingService
{
    private const BOOLEAN_FIELDS = [
        'video_watermark_default_enabled',
        'image_watermark_default_enabled',
        'pdf_watermark_default_enabled',
        'diaporama_watermark_default_enabled',
        'contact_images_enabled',
    ];

    public function update(SiteSetting $settings, array $data): SiteSetting
    {
        $logo = $data['logo'] ?? null;
        $deleteCode = $data['delete_code'] ?? null;
        unset($data['logo'], $data['delete_code']);

        if ($logo instanceof UploadedFile) {
            $data['logo_path'] = $this->replaceLogo($settings, $logo);
        }

        // Un theme absent ou vide ne change rien (la colonne est NOT NULL).
        if (blank($data['theme'] ?? null)) {
            unset($data['theme']);
        }

        // Une taille vide ne change rien (la colonne est NOT NULL).
        if (blank($data['contact_images_max_mb'] ?? null)) {
            unset($data['contact_images_max_mb']);
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

    private function replaceLogo(SiteSetting $settings, UploadedFile $logo): string
    {
        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        return $logo->store('logos', 'public');
    }
}
