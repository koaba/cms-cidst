<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [

        'hero_eyebrow',
        'facebook_url',
        'hero_eyebrow_size',
        'hero_title',
        'hero_subtitle',
        'logo_path',
        'logo_height',
        'logo_width',
        'logo_natural_height',
        'cta_primary_label',
        'cta_primary_target',
        'cta_secondary_label',
        'cta_secondary_target',
        'primary_color',
        'secondary_color',
        'theme_colors',
        'theme',
        'news_ticker_direction',
        'pages_grid_columns',
        'pages_image_size',
        'pdf_documents_title',
        'pdf_documents_content',
        'video_watermark_default_enabled',
        'image_watermark_default_enabled',
        'pdf_watermark_default_enabled',
        'diaporama_watermark_default_enabled',
        'contact_images_enabled',
        'contact_images_max_mb',
        'contact_public_email',
        'show_footer',
        'footer_description',
        'footer_show_contacts',
        'footer_copyright',
        'contact_address',
        'contact_phone',
        'contact_phone_2',
        'contact_whatsapp',
        'contact_hours',
        'map_latitude',
        'map_longitude',
    ];

    protected $hidden = ['delete_code_hash'];

    protected $casts = [
        'video_watermark_default_enabled' => 'boolean',
        'image_watermark_default_enabled' => 'boolean',
        'pdf_watermark_default_enabled' => 'boolean',
        'diaporama_watermark_default_enabled' => 'boolean',
        'contact_images_enabled' => 'boolean',
        'contact_images_max_mb' => 'integer',
        'logo_height' => 'integer',
        'logo_width' => 'integer',
        'logo_natural_height' => 'integer',
        'theme_colors' => 'array',
        'show_footer' => 'boolean',
        'footer_show_contacts' => 'boolean',
        'map_latitude' => 'float',
        'map_longitude' => 'float',
    ];

    public function getPrimaryColorAttribute($value): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value ?? '') ? $value : '#000000';
    }

    public function getSecondaryColorAttribute($value): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value ?? '') ? $value : '#FFFFFF';
    }

    private const CACHE_KEY = 'site-settings.current';

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCurrent());
        static::deleted(fn () => self::forgetCurrent());
    }

    public static function current(): self
    {
        if (app()->bound(self::CACHE_KEY)) {
            return app(self::CACHE_KEY);
        }

        // Cache inter-requetes (invalide a chaque sauvegarde) + memo pour la requete en cours.
        $settings = Cache::rememberForever(self::CACHE_KEY, fn () => static::first() ?? static::create([]));
        app()->instance(self::CACHE_KEY, $settings);

        return $settings;
    }

    private static function forgetCurrent(): void
    {
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::CACHE_KEY);
    }
}
