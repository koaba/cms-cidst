<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Expression SQL de regroupement par date, selon le pilote de la base.
 * La valeur est lue telle que stockee : le resultat est identique au formatage
 * PHP tant que la base stocke l'heure de l'application (comportement par defaut).
 */
final class SqlDate
{
    /** Format PHP => [format strftime/DATE_FORMAT, format to_char]. */
    private const FORMATS = [
        'Y-m' => ['%Y-%m', 'YYYY-MM'],
        'Y-m-d' => ['%Y-%m-%d', 'YYYY-MM-DD'],
    ];

    /**
     * @param  string  $column  nom de colonne (jamais une saisie utilisateur)
     * @param  string  $phpFormat  'Y-m' ou 'Y-m-d'
     */
    public static function bucket(string $column, string $phpFormat): string
    {
        if (! isset(self::FORMATS[$phpFormat])) {
            throw new InvalidArgumentException("Format de date non gere : {$phpFormat}");
        }

        [$percent, $toChar] = self::FORMATS[$phpFormat];
        $wrapped = DB::getQueryGrammar()->wrap($column);

        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$wrapped}, '{$percent}')",
            'pgsql' => "to_char({$wrapped}, '{$toChar}')",
            'sqlite' => "strftime('{$percent}', {$wrapped})",
            default => throw new InvalidArgumentException('Pilote de base non gere : '.DB::connection()->getDriverName()),
        };
    }
}
