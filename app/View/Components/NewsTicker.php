<?php

namespace App\View\Components;

use App\Models\Article;
use App\Models\NewsTicker as NewsTickerModel;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class NewsTicker extends Component
{
    /** Nombre maximum d'articles récents injectés dans le bandeau */
    private const MAX_RECENT_ARTICLES = 5;
    /** Un article n'est considéré "récent" que s'il a été publié il y a moins de X jours */
    private const RECENT_DAYS = 30;

    public Collection $items;
    public string $direction;

    public function __construct()
    {
        $recentArticles = Article::where('is_published', true)
            ->where('published_at', '<=', now())
            ->where('published_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->orderByDesc('published_at')
            ->limit(self::MAX_RECENT_ARTICLES)
            ->get()
            ->map(fn (Article $article) => (object) [
                'content'  => $article->title,
                'link_url' => route('blog.show', $article),
            ]);

        $manualTickers = NewsTickerModel::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn (NewsTickerModel $ticker) => (object) [
                'content'  => $ticker->content,
                'link_url' => $ticker->link_url,
            ]);

        // Articles récents d'abord, tickers manuels ensuite (choix demandé)
        $this->items = $recentArticles->concat($manualTickers);
        $this->direction = SiteSetting::current()->news_ticker_direction ?? 'horizontal';
    }

    public function render()
    {
        return view('components.news-ticker');
    }
}