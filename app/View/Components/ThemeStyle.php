<?php

namespace App\View\Components;

use App\Models\SiteSetting;
use App\Services\Festival\FestivalTheme;
use App\Services\Theme\ThemeStyleBuilder;
use Illuminate\View\Component;
use Illuminate\View\View;

class ThemeStyle extends Component
{
    public string $css;

    /** $festive : site public seulement ; l'administration garde toujours le theme choisi. */
    public function __construct(ThemeStyleBuilder $builder, FestivalTheme $festival, bool $festive = false)
    {
        $settings = SiteSetting::current();
        $festivalTheme = $festive ? $festival->key(now()) : null;

        // Valeur brute : un JSON absent, invalide ou qui n'est pas un tableau est ignoré.
        $raw = $settings->getRawOriginal('theme_colors');
        $colors = is_string($raw) ? json_decode($raw, true) : null;

        $this->css = $builder->css(
            $settings->getRawOriginal('primary_color'),
            $settings->getRawOriginal('secondary_color'),
            $festivalTheme ?? $settings->getRawOriginal('theme'),
            $festivalTheme === null && is_array($colors) ? $colors : null,
        );
    }

    public function render(): View
    {
        return view('components.theme-style');
    }
}
