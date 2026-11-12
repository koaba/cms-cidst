<?php

namespace App\Services\Theme;

use InvalidArgumentException;

/**
 * Valeur de chacun des sept rôles de couleur, résolue par couches : les défauts de
 * ThemeRoles, puis chaque couche donnée, la dernière l'emportant. Une valeur invalide
 * ou un rôle inconnu est ignoré (la valeur de la couche précédente reste).
 */
final class ThemePalette
{
    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    /** @param  array<string, string>  $colors */
    private function __construct(private readonly array $colors) {}

    /**
     * @param  array<string, mixed>  ...$layers  Du plus faible au plus fort.
     */
    public static function resolve(array ...$layers): self
    {
        $colors = ThemeRoles::defaults();

        foreach ($layers as $layer) {
            foreach (ThemeRoles::keys() as $role) {
                $value = $layer[$role] ?? null;

                if (is_string($value) && preg_match(self::HEX, $value)) {
                    $colors[$role] = strtoupper($value);
                }
            }
        }

        return new self($colors);
    }

    public function color(string $role): string
    {
        return $this->colors[$role] ?? throw new InvalidArgumentException("Rôle de couleur inconnu : « {$role} ».");
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->colors;
    }
}
