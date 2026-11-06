<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Recherche "contient" sur une colonne, sans que les % et _ saisis par le
 * visiteur soient interpretes comme jokers. ESCAPE '!' est portable (SQLite,
 * MySQL, PostgreSQL), contrairement a l'antislash dont le role varie selon la base.
 */
final class Like
{
    public static function escape(string $term): string
    {
        return strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']);
    }

    /**
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public static function contains(Builder $query, string $column, string $term): Builder
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        return $query->whereRaw($wrapped." like ? escape '!'", ['%'.self::escape($term).'%']);
    }
}
