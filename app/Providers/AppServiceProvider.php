<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Media;
use App\Models\Menu;
use App\Models\NewsTicker;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PdfDocument;
use App\Models\SeoMeta;
use App\Models\SiteSetting;
use App\Observers\PublicLayoutCacheObserver;
use App\Observers\PublicPageCacheObserver;
use App\Observers\SitemapCacheObserver;
use App\Support\PublicLayout;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return $this->app->isProduction()
                ? Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
                : Password::min(6);
        });

        View::composer('components.layout', function ($view) {
            $view->with('siteSettings', SiteSetting::current());
        });

        $this->app->scoped(PublicLayout::class);

        Menu::observe(PublicLayoutCacheObserver::class);
        NewsTicker::observe(PublicLayoutCacheObserver::class);
        Article::observe(PublicLayoutCacheObserver::class);
        Media::observe(PublicLayoutCacheObserver::class);

        foreach ([Page::class, PageBlock::class, SeoMeta::class, Media::class, PdfDocument::class] as $model) {
            $model::observe(PublicPageCacheObserver::class);
        }

        Page::observe(SitemapCacheObserver::class);
        Article::observe(SitemapCacheObserver::class);
        SeoMeta::observe(SitemapCacheObserver::class);
    }
}
