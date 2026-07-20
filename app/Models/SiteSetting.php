<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'hero_eyebrow',
        'hero_title',
        'hero_subtitle',
        'cta_primary_label',
        'cta_primary_target',
        'cta_secondary_label',
        'cta_secondary_target',
    ];

    /**
     * Récupère l'unique ligne de réglages, la crée avec des valeurs par défaut si elle n'existe pas encore.
     */
    public static function current(): self
    {
        return static::first() ?? static::create([]);
    }
}