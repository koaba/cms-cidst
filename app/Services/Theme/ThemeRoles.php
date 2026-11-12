<?php

namespace App\Services\Theme;

use InvalidArgumentException;

/**
 * Les sept rôles de couleur du thème, avec leur libellé et leur défaut.
 *
 * Classe de code et non fichier de configuration : un rôle exige une variable CSS
 * (--color-{rôle}-rgb, défaut dans resources/css/shared.css) et un jeton Tailwind
 * (tailwind.tokens.js). ThemeTokensTest vérifie que les trois restent alignés.
 *
 * Les défauts sont les couleurs du site. Le texte secondaire est #4B5057 (contraste 7,25
 * sur le fond, 8,13 sur blanc) : l'ancien #6B7078 passait sous 4,5 sur le fond.
 */
final class ThemeRoles
{
    public const PRIMARY_CONTENT_VARIABLE = '--color-primary-content-rgb';

    private const DEFINITIONS = [
        'primary' => ['label' => 'Couleur primaire', 'default' => '#C41E2A'],
        'secondary' => ['label' => 'Couleur secondaire', 'default' => '#17181A'],
        'bg' => ['label' => 'Fond de page', 'default' => '#F1F2F4'],
        'surface' => ['label' => 'Surface (menu, cartes)', 'default' => '#FFFFFF'],
        'text' => ['label' => 'Texte principal', 'default' => '#17181A'],
        'muted' => ['label' => 'Texte secondaire', 'default' => '#4B5057'],
        'border' => ['label' => 'Bordures', 'default' => '#D8DADE'],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    /** @return array<string, string> */
    public static function defaults(): array
    {
        return array_map(fn (array $definition): string => $definition['default'], self::DEFINITIONS);
    }

    public static function label(string $role): string
    {
        return self::definition($role)['label'];
    }

    /** Variable CSS du rôle, au format « R G B » utilisé par Tailwind. */
    public static function cssVariable(string $role): string
    {
        self::definition($role);

        return '--color-'.$role.'-rgb';
    }

    /** @return array{label: string, default: string} */
    private static function definition(string $role): array
    {
        return self::DEFINITIONS[$role] ?? throw new InvalidArgumentException("Rôle de couleur inconnu : « {$role} ».");
    }
}
