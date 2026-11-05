<?php

namespace App\Http\Controllers;

use App\Services\SitemapUrlBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public const CACHE_KEY = 'sitemap.xml';

    public function index(SitemapUrlBuilder $builder): Response
    {
        $urls = Cache::remember(self::CACHE_KEY, 3600, fn () => $builder->build());

        $xml = view('sitemap.index', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
