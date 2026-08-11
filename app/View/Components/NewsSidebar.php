<?php

namespace App\View\Components;

use App\Models\Article;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class NewsSidebar extends Component
{
    private const MAX_ARTICLES = 5;

    public Collection $articles;
    public ?string $facebookUrl;

    public function __construct()
    {
        $this->articles = Article::where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(self::MAX_ARTICLES)
            ->get();

        $this->facebookUrl = SiteSetting::current()->facebook_url;
    }

    public function render()
    {
        return view('components.news-sidebar');
    }
}