<?php

namespace App\View\Components;

use App\Models\SiteSetting;
use App\Services\Theme\ThemeStyleBuilder;
use Illuminate\View\Component;
use Illuminate\View\View;

class ThemeStyle extends Component
{
    public string $css;

    public function __construct(ThemeStyleBuilder $builder)
    {
        $settings = SiteSetting::current();

        $this->css = $builder->css(
            $settings->getRawOriginal('primary_color'),
            $settings->getRawOriginal('secondary_color'),
        );
    }

    public function render(): View
    {
        return view('components.theme-style');
    }
}
