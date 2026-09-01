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

    public static function current(): self
    {
        return static::first() ?? static::create([]);
    }
}
