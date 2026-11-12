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

    public function __construct(
        private readonly ColorConverter $converter,
        private readonly ContrastChecker $contrast = new ContrastChecker,
    ) {}

    /**
     * Couches, la dernière l'emporte : défauts des rôles, réglages historiques (primary_color,
     * secondary_color), palette du thème, puis couleurs personnalisées (site_settings.theme_colors).
     *
     * @param  array<string, mixed>|null  $colors  Couleurs personnalisées par rôle ; invalides ignorées.
     */
    public function css(?string $primary, ?string $secondary, ?string $theme = null, ?array $colors = null): string
    {
        $palette = $this->palette($theme);

        $roles = $this->roles($primary, $secondary, $theme, $colors);
        $primary = $roles->color('primary');
        $secondary = $roles->color('secondary');
        $accent = $this->valid($palette['accent'] ?? $primary, $primary);

        // Une couleur personnalisée remplace celle du thème : son texte imposé ne vaut plus, on le recalcule.
        $primaryContent = $this->content($this->customized($colors, 'primary') ? null : ($palette['primary_content'] ?? null), $primary);
        $secondaryContent = $this->content($this->customized($colors, 'secondary') ? null : ($palette['secondary_content'] ?? null), $secondary);
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

        foreach (['bg', 'surface', 'text', 'muted', 'border'] as $role) {
            $vars[ThemeRoles::cssVariable($role)] = $this->converter->rgbTriplet($roles->color($role));
        }

        $vars[ThemeRoles::PRIMARY_CONTENT_VARIABLE] = $this->converter->rgbTriplet($this->contrast->readableTextOn($primary));

        $declarations = '';
        foreach ($vars as $name => $value) {
            $declarations .= $name.':'.$value.';';
        }

        return 'html:root{'.$declarations.'}';
    }

    /**
     * Valeur des sept rôles pour ces réglages, par les mêmes couches que css() : défauts, réglages
     * historiques, palette du thème, couleurs personnalisées. Sert au formulaire des réglages.
     *
     * @param  array<string, mixed>|null  $colors
     */
    public function roles(?string $primary, ?string $secondary, ?string $theme = null, ?array $colors = null): ThemePalette
    {
        return ThemePalette::resolve(['primary' => $primary, 'secondary' => $secondary], $this->palette($theme) ?? [], $colors ?? []);
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

    /** @param  array<string, mixed>|null  $colors */
    private function customized(?array $colors, string $role): bool
    {
        return preg_match(self::HEX, is_string($colors[$role] ?? null) ? $colors[$role] : '') === 1;
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
