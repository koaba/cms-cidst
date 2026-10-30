<?php

namespace App\View\Components;

use App\Models\SiteSetting;
use App\Support\PublicLayout;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class NewsSidebar extends Component
{
    public Collection $articles;

    public ?string $facebookUrl;

    public function __construct()
    {
        $this->articles = app(PublicLayout::class)->sidebarArticles();
        $this->facebookUrl = SiteSetting::current()->facebook_url;
    }

    public function render()
    {
        return view('components.news-sidebar');
    }
}
