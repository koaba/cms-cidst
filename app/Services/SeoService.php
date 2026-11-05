<?php

namespace App\Services;

use App\Contracts\HasPublicUrl;
use App\Models\Article;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
            ? $modelTitle.' — '.self::siteName()
            : self::siteName();
    }

    public static function description(Model $model): ?string
    {
        $manual = $model->seo?->meta_description;
        if ($manual) {
            return $manual;
        }

        $content = $model->content ?: self::textFromBlocks($model);
        if (! $content) {
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

        if ($model instanceof Article && $model->coverMedia) {
            return Storage::disk('public')->url($model->coverMedia->path);
        }

        if (! empty($model->image)) {
            return Storage::disk('public')->url($model->image);
        }

        if (method_exists($model, 'media')) {
            $firstMedia = $model->relationLoaded('media')
                ? $model->media->first(fn ($media) => str_starts_with((string) $media->mime_type, 'image/'))
                : $model->media()->where('media.mime_type', 'like', 'image/%')->first();
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
        return $model instanceof Article ? 'article' : 'website';
    }

    /**
     * SEO d'une page sans modele (accueil, liste, contact, recherche), lu dans
     * config/seo.php. $replace complete les jetons (ex. ['name' => 'Physique']).
     *
     * @return array{title: string, description: string, noindex: bool}
     */
    public static function forPage(string $key, array $replace = []): array
    {
        $meta = config("seo.pages.{$key}")
            ?? throw new InvalidArgumentException("Page SEO inconnue : {$key}");

        $tokens = [':site' => self::siteName()];
        foreach ($replace as $token => $value) {
            $tokens[':'.$token] = $value;
        }

        return [
            'title' => strtr($meta['title'], $tokens),
            'description' => Str::limit(strtr($meta['description'], $tokens), 160, '…'),
            'noindex' => (bool) ($meta['noindex'] ?? false),
        ];
    }

    /**
     * Canonical d'une page sans modele : l'URL courante sans query string,
     * sauf le numero de page d'une liste paginee (page 2 et suivantes).
     */
    public static function listCanonical(): string
    {
        $page = request()->integer('page', 1);

        return $page > 1 ? url()->current().'?page='.$page : url()->current();
    }

    /**
     * URL absolue du logo du site (reglages), ou null s'il n'y en a pas.
     */
    public static function logoUrl(): ?string
    {
        $path = SiteSetting::current()->logo_path;

        return $path ? asset('storage/'.$path) : null;
    }

    /**
     * Blocs JSON-LD d'une page : article et page (avec fil d'Ariane), ou
     * Organization + WebSite pour l'accueil. Le premier bloc est l'entite principale.
     *
     * @return list<array<string, mixed>>
     */
    public static function structuredData(?Model $model, ?string $seoPage): array
    {
        return match (true) {
            $model instanceof Article => [self::articleData($model), self::breadcrumbData($model)],
            $model instanceof Page => [self::pageData($model), self::breadcrumbData($model)],
            $seoPage === 'home' => [self::siteData()],
            default => [],
        };
    }

    private static function organization(): array
    {
        return array_filter([
            '@type' => 'Organization',
            'name' => self::siteName(),
            'url' => url('/'),
            'logo' => self::logoUrl(),
        ]);
    }

    private static function articleData(Article $article): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $article->title,
            'description' => self::description($article),
            'image' => array_filter([self::image($article)]),
            'datePublished' => optional($article->published_at)->toIso8601String(),
            'dateModified' => optional($article->updated_at)->toIso8601String(),
            'author' => self::organization(),
            'publisher' => self::organization(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $article->publicUrl(),
            ],
        ];
    }

    private static function pageData(Page $page): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $page->title,
            'description' => self::description($page),
            'url' => $page->publicUrl(),
            'dateModified' => optional($page->updated_at)->toIso8601String(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => self::siteName(),
                'url' => url('/'),
            ],
        ]);
    }

    private static function siteData(): array
    {
        $organizationId = url('/').'#organization';

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@id' => $organizationId] + self::organization(),
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => self::siteName(),
                    'inLanguage' => 'fr',
                    'publisher' => ['@id' => $organizationId],
                ],
            ],
        ];
    }

    /**
     * Fil d'Ariane : Accueil > (Blog >) titre. Libelles dans config/seo.php.
     */
    private static function breadcrumbData(Article|Page $model): array
    {
        $trail = [[config('seo.breadcrumbs.home'), url('/')]];

        if ($model instanceof Article) {
            $trail[] = [config('seo.breadcrumbs.blog'), route('blog.index')];
        }

        $trail[] = [$model->title, $model->publicUrl()];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $step, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $step[0],
                'item' => $step[1],
            ], $trail, array_keys($trail)),
        ];
    }

    /**
     * Encode des données JSON-LD pour un bloc <script> : < > et & sont
     * échappés pour qu'une valeur saisie (titre...) ne puisse jamais
     * refermer la balise.
     */
    public static function jsonLd(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
                | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Pour un modèle à blocs (Page), dérive le texte du premier bloc
     * racine porteur de texte, quand `content` est vide.
     */
    protected static function textFromBlocks(Model $model): ?string
    {
        if (! method_exists($model, 'blocks')) {
            return null;
        }

        foreach ($model->blocks->whereNull('parent_id') as $block) {
            if (! in_array($block->type, ['texte', 'section_fond', 'citation'], true)) {
                continue;
            }

            $text = $block->data['content'] ?? $block->data['text'] ?? null;
            if ($text) {
                return $text;
            }
        }

        return null;
    }

    protected static function siteName(): string
    {
        return config('app.name', 'CIDST');
    }
}
