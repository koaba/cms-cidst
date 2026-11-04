<?php

namespace App\Enums;

/**
 * Noms des roles Spatie : seul endroit ou ils sont ecrits.
 * Ajouter un role = ajouter un case ici (et son seeder).
 */
enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case Publication = 'Publication';

    /**
     * Valeur pour le middleware Spatie, ex. 'role:Super Admin|Publication'.
     */
    public static function middleware(self ...$roles): string
    {
        return 'role:'.implode('|', self::valuesOf($roles));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return self::valuesOf(self::cases());
    }

    /**
     * @param  list<self>  $roles
     * @return list<string>
     */
    private static function valuesOf(array $roles): array
    {
        return array_map(fn (self $role) => $role->value, $roles);
    }
}
