<?php

namespace App\Services;

use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Centralise la résolution des métadonnées SEO publiques pour tout
 * modèle utilisant le trait HasSeo (Article, Page, futur Event...).
 *
 * Ordre de priorité pour chaque champ :
 *   1. Valeur saisie manuellement dans seo_metas (via l'admin)
 *   2. Fallback dérivé du contenu du modèle
 *   3. Fallback générique (nom du site, etc.)
 */
class SeoService
{
    public static function title(Model $model): string
    {
        $manual = $model->seo?->meta_title;
        if ($manual) {
            return $manual;
        }

        $modelTitle = $model->title ?? null;

        return $modelTitle
            ? $modelTitle . ' — ' . self::siteName()
            : self::siteName();
    }

    public static function description(Model $model): ?string
    {
        $manual = $model->seo?->meta_description;
        if ($manual) {
            return $manual;
        }

        $content = $model->content ?? null;
        if (!$content) {
            return null;
        }

        return Str::limit(trim(strip_tags($content)), 160, '…');
    }

    public static function image(Model $model): ?string
    {
        $manual = $model->seo?->og_image;
        if ($manual) {
            return Storage::disk('public')->url($manual);
        }

        if (!empty($model->image)) {
            return Storage::disk('public')->url($model->image);
        }

        if (method_exists($model, 'media')) {
            $firstMedia = $model->media()->first();
            if ($firstMedia) {
                return Storage::disk('public')->url($firstMedia->path);
            }
        }

        return null;
    }

    public static function canonical(Model $model): string
    {
        $manual = $model->seo?->canonical_url;
        if ($manual) {
            return $manual;
        }

        if ($model instanceof HasPublicUrl) {
            return $model->publicUrl();
        }

        return url()->current();
    }

    public static function noIndex(Model $model): bool
    {
        return (bool) ($model->seo?->no_index ?? false);
    }

    public static function ogType(Model $model): string
    {
        return $model instanceof \App\Models\Article ? 'article' : 'website';
    }

    protected static function siteName(): string
    {
        return config('app.name', 'CIDST');
    }
}