<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Menu;
use App\Models\NewsTicker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Donnees communes a toutes les pages publiques (menu, ticker, sidebar),
 * calculees une fois puis mises en cache. Une seule lecture de cache par requete.
 *
 * Invalidation : App\Observers\PublicLayoutCacheObserver (modeles concernes) ;
 * le TTL couvre les changements dus au temps (article programme qui devient visible).
 */
class PublicLayout
{
    public const CACHE_KEY = 'public.layout';

    private ?array $data = null;

    public function menu(): Collection
    {
        return $this->data()['menu'];
    }

    /** Articles recents du ticker (modeles) ; les tickers manuels sont dans tickers(). */
    public function tickerArticles(): Collection
    {
        return $this->data()['ticker_articles'];
    }

    public function tickers(): Collection
    {
        return $this->data()['tickers'];
    }

    public function sidebarArticles(): Collection
    {
        return $this->data()['sidebar_articles'];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function data(): array
    {
        return $this->data ??= Cache::remember(
            self::CACHE_KEY,
            (int) config('display.layout_cache_ttl'),
            fn () => $this->build(),
        );
    }

    private function build(): array
    {
        return [
            'menu' => Menu::activeTree(),
            'ticker_articles' => Article::where('is_published', true)
                ->where('published_at', '<=', now())
                ->where('published_at', '>=', now()->subDays(config('display.ticker_recent_days')))
                ->orderByDesc('published_at')
                ->limit(config('display.max_ticker_articles'))
                ->get(),
            'tickers' => NewsTicker::where('is_active', true)->orderBy('order')->get(),
            'sidebar_articles' => Article::where('is_published', true)
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(config('display.max_sidebar_articles'))
                ->with('coverMedia')
                ->get(),
        ];
    }
}
