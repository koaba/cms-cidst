<?php

namespace App\Services\Theme;

use InvalidArgumentException;

/**
 * Convertit une couleur #RRGGBB vers les formats du thème :
 * triplet RGB pour Tailwind (rgb(var(--x) / <alpha-value>)) et OKLCH pour DaisyUI 4.
 *
 * Reproduit daisyui/src/theming/functions.js (v4.12.2) : sortie « L% C H » arrondie
 * à 6 décimales ; couleur de contraste = mélange à 80 % en OKLCH vers le blanc si la
 * couleur est sombre (contraste WCAG), vers le noir sinon.
 */
final class ColorConverter
{
    private const CONTENT_MIX = 0.8;

    /** « R G B » en entiers décimaux. */
    public function rgbTriplet(string $hex): string
    {
        return implode(' ', $this->channels($hex));
    }

    /** Couleur au format DaisyUI : « L% C H ». */
    public function daisyOklch(string $hex): string
    {
        return $this->format(...$this->oklch($hex));
    }

    /** Couleur de contraste (…-content) calculée comme DaisyUI. */
    public function contentOklch(string $hex): string
    {
        [$l, $c, $h] = $this->oklch($hex);
        $target = $this->isDark($hex) ? 1.0 : 0.0;

        return $this->format($l + self::CONTENT_MIX * ($target - $l), $c * (1 - self::CONTENT_MIX), $h);
    }

    /** @return array{int, int, int} */
    private function channels(string $hex): array
    {
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            throw new InvalidArgumentException("Couleur invalide : attendu #RRGGBB, reçu « {$hex} ».");
        }

        return array_map('hexdec', str_split(substr($hex, 1), 2));
    }

    /** @return array{float, float, float} RGB linéaire (0..1). */
    private function linear(string $hex): array
    {
        return array_map(function (int $v): float {
            $v /= 255;

            return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, $this->channels($hex));
    }

    /** @return array{float, float, float} L (0..1), C, H (degrés). */
    private function oklch(string $hex): array
    {
        [$r, $g, $b] = $this->linear($hex);

        $l = (0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b) ** (1 / 3);
        $m = (0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b) ** (1 / 3);
        $s = (0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b) ** (1 / 3);

        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $bb = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;
        $chroma = hypot($a, $bb);

        return [
            0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s,
            $chroma,
            round($chroma, 6) === 0.0 ? 0.0 : fmod(rad2deg(atan2($bb, $a)) + 360, 360),
        ];
    }

    /** Même test que DaisyUI : contraste WCAG avec le noir < contraste avec le blanc. */
    private function isDark(string $hex): bool
    {
        [$r, $g, $b] = $this->linear($hex);
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return ($luminance + 0.05) / 0.05 < 1.05 / ($luminance + 0.05);
    }

    private function format(float $l, float $c, float $h): string
    {
        return sprintf('%s%% %s %s', $this->number(round($l, 6) * 100), $this->number($c), $this->number($h));
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
