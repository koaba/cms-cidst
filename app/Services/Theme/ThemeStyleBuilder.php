<?php

namespace App\Services\Theme;

/**
 * Produit le bloc CSS du thème (couleurs primary et secondary) à partir de
 * valeurs #RRGGBB : variables DaisyUI (OKLCH) et triplets RGB pour Tailwind.
 * Une valeur absente ou invalide retombe sur le défaut du thème « cidst ».
 */
final class ThemeStyleBuilder
{
    public const DEFAULT_PRIMARY = '#C41E2A';

    public const DEFAULT_SECONDARY = '#17181A';

    public function __construct(private readonly ColorConverter $converter) {}

    public function css(?string $primary, ?string $secondary): string
    {
        $primary = $this->valid($primary, self::DEFAULT_PRIMARY);
        $secondary = $this->valid($secondary, self::DEFAULT_SECONDARY);

        $vars = [
            '--p' => $this->converter->daisyOklch($primary),
            '--pc' => $this->converter->contentOklch($primary),
            '--a' => $this->converter->daisyOklch($primary),
            '--ac' => $this->converter->contentOklch($primary),
            '--s' => $this->converter->daisyOklch($secondary),
            '--sc' => $this->converter->contentOklch($secondary),
            '--n' => $this->converter->daisyOklch($secondary),
            '--nc' => $this->converter->contentOklch($secondary),
            '--color-primary-rgb' => $this->converter->rgbTriplet($primary),
            '--color-secondary-rgb' => $this->converter->rgbTriplet($secondary),
        ];

        $declarations = '';
        foreach ($vars as $name => $value) {
            $declarations .= $name.':'.$value.';';
        }

        return 'html:root{'.$declarations.'}';
    }

    private function valid(?string $hex, string $default): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $hex ?? '') ? $hex : $default;
    }
}
