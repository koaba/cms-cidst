<?php

namespace App\Services\Theme;

/**
 * Produit le bloc CSS du thème (couleurs primary, secondary et accent) à partir de
 * valeurs #RRGGBB : variables DaisyUI (OKLCH) et triplets RGB pour Tailwind.
 * Une valeur absente ou invalide retombe sur le défaut du thème « cidst ».
 *
 * Un thème de config/themes.php avec une palette impose ses couleurs (et ses couleurs
 * de texte) ; sans palette (cidst), ce sont les couleurs des réglages qui s'appliquent.
 */
final class ThemeStyleBuilder
{
    public const DEFAULT_PRIMARY = '#C41E2A';

    public const DEFAULT_SECONDARY = '#17181A';

    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    public function __construct(private readonly ColorConverter $converter) {}

    public function css(?string $primary, ?string $secondary, ?string $theme = null): string
    {
        $palette = $this->palette($theme);

        $primary = $this->valid($palette['primary'] ?? $primary, self::DEFAULT_PRIMARY);
        $secondary = $this->valid($palette['secondary'] ?? $secondary, self::DEFAULT_SECONDARY);
        $accent = $this->valid($palette['accent'] ?? $primary, $primary);

        $primaryContent = $this->content($palette['primary_content'] ?? null, $primary);
        $secondaryContent = $this->content($palette['secondary_content'] ?? null, $secondary);
        $accentContent = $this->content($palette['accent_content'] ?? null, $accent);

        $vars = [
            '--p' => $this->converter->daisyOklch($primary),
            '--pc' => $primaryContent,
            '--a' => $this->converter->daisyOklch($accent),
            '--ac' => $accentContent,
            '--s' => $this->converter->daisyOklch($secondary),
            '--sc' => $secondaryContent,
            '--n' => $this->converter->daisyOklch($secondary),
            '--nc' => $secondaryContent,
            '--color-primary-rgb' => $this->converter->rgbTriplet($primary),
            '--color-secondary-rgb' => $this->converter->rgbTriplet($secondary),
        ];

        $declarations = '';
        foreach ($vars as $name => $value) {
            $declarations .= $name.':'.$value.';';
        }

        return 'html:root{'.$declarations.'}';
    }

    /** @return array<string, string>|null Palette du thème, null pour cidst ou un thème inconnu. */
    private function palette(?string $theme): ?array
    {
        if ($theme === null) {
            return null;
        }

        $palette = config('themes.themes', [])[$theme]['palette'] ?? null;

        return is_array($palette) ? $palette : null;
    }

    /** Couleur de texte imposée par le thème, sinon calculée comme DaisyUI. */
    private function content(?string $explicit, string $color): string
    {
        return preg_match(self::HEX, $explicit ?? '')
            ? $this->converter->daisyOklch($explicit)
            : $this->converter->contentOklch($color);
    }

    private function valid(?string $hex, string $default): string
    {
        return preg_match(self::HEX, $hex ?? '') ? $hex : $default;
    }
}
