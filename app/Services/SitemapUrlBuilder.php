<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Page;
use App\Models\PdfDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Construit la liste des URL du sitemap : accueil, pages de liste non vides,
 * puis chaque article, page et document PDF indexable.
 */
class SitemapUrlBuilder
{
    /**
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    public function build(): Collection
    {
        $articles = Article::publiclyVisible()->with('seo')->get();
        $pages = Page::publiclyVisible()->with('seo')->get();
        $documents = PdfDocument::all();

        return collect([['loc' => url('/'), 'lastmod' => null]])
            ->concat($this->listing(route('blog.index'), $articles))
            ->concat($this->listing(route('pages.index'), $pages))
            ->concat($this->listing(route('documents.index'), $documents))
            ->concat($this->entries($this->indexable($articles)))
            ->concat($this->entries($this->indexable($pages)))
            ->concat($this->entries($documents))
            ->values();
    }

    /**
     * Une page de liste n'existe dans le sitemap que si elle a du contenu ;
     * sa date est celle de la derniere modification, noindex compris.
     */
    private function listing(string $url, Collection $items): Collection
    {
        $latest = $items->max('updated_at');

        return collect($latest ? [['loc' => $url, 'lastmod' => $latest->toAtomString()]] : []);
    }

    private function indexable(Collection $items): Collection
    {
        return $items->reject(fn (Model $item) => $item->seo?->no_index);
    }

    private function entries(Collection $items): Collection
    {
        return $items->toBase()->map(fn (Model $item) => [
            'loc' => $item->publicUrl(),
            'lastmod' => $item->updated_at->toAtomString(),
        ]);
    }
}
