<?php

namespace App\Providers;

use App\Models\SiteSetting;
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
    }
}