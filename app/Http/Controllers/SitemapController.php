<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = Cache::remember('sitemap.xml', 3600, function () {
            $articles = Article::publiclyVisible()->with('seo')->get()
                ->reject(fn (Article $article) => $article->seo?->no_index)
                ->map(fn (Article $article) => [
                    'loc' => $article->publicUrl(),
                    'lastmod' => $article->updated_at->toAtomString(),
                ]);

            $pages = Page::publiclyVisible()->with('seo')->get()
                ->reject(fn (Page $page) => $page->seo?->no_index)
                ->map(fn (Page $page) => [
                    'loc' => $page->publicUrl(),
                    'lastmod' => $page->updated_at->toAtomString(),
                ]);

            return $articles->merge($pages);
        });

        $xml = view('sitemap.index', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}