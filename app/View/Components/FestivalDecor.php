<?php

namespace App\View\Components;

use App\Services\Festival\FestivalSettings;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Decor de fete du site public : bandeau au-dessus du menu ('strip') ou icone sur le logo ('logo').
 * Rien n'est rendu hors fenetre de fete, si les fetes sont coupees (.env ou administration), si la fete est
 * desactivee ou si son emplacement ne comprend pas cette variante. Fete, date, emplacement, icones et animation
 * viennent de FestivalSettings (reglages de l'administration, valeurs par defaut de config/festivals.php).
 */
class FestivalDecor extends Component
{
    /** Cle du jeu d'icones de la fete en cours, ou null. */
    public ?string $decor = null;

    /** @var array{logo?: string, strip?: list<string>} */
    public array $icons = [];

    public bool $animated = true;

    public function __construct(FestivalSettings $settings, public string $variant = 'strip')
    {
        $festival = $settings->activeKey();
        $decor = $festival === null ? null : config("festivals.festivals.$festival.decor");

        if ($festival === null || ! is_string($decor)) {
            return;
        }

        $options = $settings->options($festival);

        if (in_array($options['placement'], ['both', $variant], true)) {
            $this->decor = $decor;
            $this->icons = ['logo' => $options['logo'], 'strip' => $settings->strip($festival)];
            $this->animated = $options['animated'];
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
