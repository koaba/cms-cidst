<?php

namespace App\View\Components;

use App\Models\Article;
use App\Models\NewsTicker as NewsTickerModel;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class NewsTicker extends Component
{
    public Collection $items;
    public string $direction;

    public function __construct()
    {
        $recentArticles = Article::where('is_published', true)
            ->where('published_at', '<=', now())
            ->where('published_at', '>=', now()->subDays(config('display.ticker_recent_days')))
            ->orderByDesc('published_at')
            ->limit(config('display.max_ticker_articles'))
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

        // Articles recents d'abord, tickers manuels ensuite (choix demande)
        $this->items = $recentArticles->concat($manualTickers);
        $this->direction = SiteSetting::current()->news_ticker_direction ?? 'horizontal';
    }

    public function render()
    {
        return view('components.news-ticker');
    }
}
