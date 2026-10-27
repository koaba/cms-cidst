<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [

        'hero_eyebrow',
        'facebook_url',
        'hero_eyebrow_size',
        'hero_title',
        'hero_subtitle',
        'logo_path',
        'cta_primary_label',
        'cta_primary_target',
        'cta_secondary_label',
        'cta_secondary_target',
        'primary_color',
        'secondary_color',
        'news_ticker_direction',
        'pages_grid_columns',
        'pages_image_size',
        'pdf_documents_title',
        'pdf_documents_content',
        'video_watermark_default_enabled',
        'image_watermark_default_enabled',
        'pdf_watermark_default_enabled',
        'diaporama_watermark_default_enabled',
    ];

    protected $hidden = ['delete_code_hash'];

    protected $casts = [
        'video_watermark_default_enabled' => 'boolean',
        'image_watermark_default_enabled' => 'boolean',
        'pdf_watermark_default_enabled' => 'boolean',
        'diaporama_watermark_default_enabled' => 'boolean',
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
        static::saved(fn () => app()->forgetInstance(self::CACHE_KEY));
        static::deleted(fn () => app()->forgetInstance(self::CACHE_KEY));
    }

    public static function current(): self
    {
        if (app()->bound(self::CACHE_KEY)) {
            return app(self::CACHE_KEY);
        }

        $settings = static::first() ?? static::create([]);
        app()->instance(self::CACHE_KEY, $settings);

        return $settings;
    }
}
