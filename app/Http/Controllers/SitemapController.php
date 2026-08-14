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
            $articles = Article::publiclyVisible()->get()->map(function (Article $article) {
                return [
                    'loc' => $article->publicUrl(),
                    'lastmod' => $article->updated_at->toAtomString(),
                ];
            });

            $pages = Page::publiclyVisible()->get()->map(function (Page $page) {
                return [
                    'loc' => $page->publicUrl(),
                    'lastmod' => $page->updated_at->toAtomString(),
                ];
            });

            return $articles->merge($pages);
        });

        $xml = view('sitemap.index', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}