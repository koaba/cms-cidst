<?php

namespace App\Support;

use App\Models\Page;
use App\Models\PdfDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Cache de l'arbre de rendu d'une page publique : seo, medias, blocs (avec medias
 * et enfants rattaches) et documents PDF references. La page elle-meme reste lue
 * en base (route binding), donc statut, dates et titre sont toujours frais.
 *
 * Invalidation globale par "version" : tout changement de contenu (observer,
 * BlockSyncService::sync) fait tourner la version, ce qui rend toutes les entrees
 * inaccessibles ; elles se reconstruisent a la demande. Le TTL est le filet de
 * securite pour les ecritures qui n'emettent pas d'evenement (pivots, SQL direct).
 */
class PublicPageCache
{
    private const VERSION_KEY = 'public.pages.version';

    /** @return array{seo: mixed, media: mixed, blocks: mixed, pdfDocuments: mixed} */
    public static function tree(Page $page): array
    {
        return Cache::remember(
            'public.page.'.self::version().'.'.$page->getKey(),
            (int) config('display.page_cache_ttl'),
            fn () => self::build($page),
        );
    }

    /** Applique l'arbre en cache sur la page et retourne les documents PDF prechargés. */
    public static function hydrate(Page $page): Collection
    {
        $tree = self::tree($page);

        $page->setRelation('seo', $tree['seo'])
            ->setRelation('media', $tree['media'])
            ->setRelation('blocks', $tree['blocks']);

        return $tree['pdfDocuments'];
    }

    public static function forgetAll(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }

    private static function version(): string
    {
        return Cache::rememberForever(self::VERSION_KEY, fn () => (string) Str::uuid());
    }

    private static function build(Page $page): array
    {
        $page->loadBlockTree();

        $pdfIds = $page->blocks->where('type', 'pdf')->pluck('data.pdf_document_id')->filter()->unique();

        return [
            'seo' => $page->seo,
            'media' => $page->media,
            'blocks' => $page->blocks,
            'pdfDocuments' => PdfDocument::with('pdfs')->whereIn('id', $pdfIds)->get()->keyBy('id'),
        ];
    }
}
