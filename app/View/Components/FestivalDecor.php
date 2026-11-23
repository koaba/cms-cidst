<?php

namespace App\View\Components;

use App\Services\Festival\FestivalCalendar;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Decor de fete du site public : bandeau au-dessus du menu ('strip') ou icone sur le logo ('logo').
 * Rien n'est rendu hors fenetre de fete ni si l'interrupteur est coupe (meme calendrier et meme interrupteur
 * que la palette). Les icones viennent de config('festivals.decors').
 */
class FestivalDecor extends Component
{
    /** Cle du jeu d'icones de la fete en cours, ou null. */
    public ?string $decor = null;

    /** @var array<string, mixed> */
    public array $icons = [];

    public function __construct(FestivalCalendar $calendar, public string $variant = 'strip')
    {
        $key = config('festivals.enabled') ? $calendar->activeOn(now()) : null;
        $decor = $key === null ? null : config("festivals.festivals.$key.decor");
        $icons = is_string($decor) ? config("festivals.decors.$decor") : null;

        if (is_array($icons)) {
            $this->decor = $decor;
            $this->icons = $icons;
        }
    }

    public function shouldRender(): bool
    {
        return $this->decor !== null;
    }

    public function render(): View
    {
        return view('components.festival-decor');
    }
}
