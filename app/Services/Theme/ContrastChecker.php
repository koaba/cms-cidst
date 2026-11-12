<?php

namespace App\Services\Theme;

use InvalidArgumentException;

/**
 * Contraste WCAG entre deux couleurs #RRGGBB. Un seul calcul pour le thème, le garde-fou
 * des réglages et les tests : seuil de 4,5 pour un texte courant.
 */
final class ContrastChecker
{
    public const MINIMUM = 4.5;

    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    /** Rapport de contraste, de 1 (identiques) à 21 (noir sur blanc), dans les deux sens. */
    public function ratio(string $a, string $b): float
    {
        $first = $this->luminance($a);
        $second = $this->luminance($b);

        return (max($first, $second) + 0.05) / (min($first, $second) + 0.05);
    }

    public function passes(string $foreground, string $background, float $minimum = self::MINIMUM): bool
    {
        return $this->ratio($foreground, $background) >= $minimum;
    }

    /** Blanc ou noir, selon le meilleur contraste sur ce fond (blanc à égalité). */
    public function readableTextOn(string $background): string
    {
        return $this->ratio('#FFFFFF', $background) >= $this->ratio('#000000', $background) ? '#FFFFFF' : '#000000';
    }

    private function luminance(string $hex): float
    {
        if (! preg_match(self::HEX, $hex)) {
            throw new InvalidArgumentException("Couleur invalide : attendu #RRGGBB, reçu « {$hex} ».");
        }

        [$r, $g, $b] = array_map(function (string $part): float {
            $value = hexdec($part) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
