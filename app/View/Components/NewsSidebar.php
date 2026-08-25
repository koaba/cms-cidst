<?php

namespace App\View\Components;

use App\Models\Article;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class NewsSidebar extends Component
{
    public Collection $articles;
    public ?string $facebookUrl;

    public function __construct()
    {
        $this->articles = Article::where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(config('display.max_sidebar_articles'))
            ->get();
        $this->facebookUrl = SiteSetting::current()->facebook_url;
    }

    public function render()
    {
        return view('components.news-sidebar');
    }
}