<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
  protected $fillable = [
    'hero_eyebrow',
    'hero_eyebrow_size',
    'hero_title',
    'hero_subtitle',
    'logo_path',
    'cta_primary_label',
    'cta_primary_target',
    'cta_secondary_label',
    'cta_secondary_target',
];

    protected $casts = [
        'hero_pattern_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::first() ?? static::create([]);
    }
}