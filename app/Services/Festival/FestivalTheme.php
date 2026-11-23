<?php

namespace App\Services\Festival;

use DateTimeInterface;

/**
 * Theme de fete a appliquer au site public un jour donne : la cle du theme de config/themes.php lie
 * a la fete en cours (config/festivals.php), ou null hors fenetre, si les fetes sont coupees
 * (FESTIVALS_ENABLED) ou si le theme de la fete n'existe pas.
 */
final class FestivalTheme
{
    public function __construct(private readonly FestivalCalendar $calendar) {}

    public function key(DateTimeInterface $date): ?string
    {
        if (! config('festivals.enabled', true)) {
            return null;
        }

        $festival = $this->calendar->activeOn($date);
        $theme = $festival === null ? null : config('festivals.festivals.'.$festival.'.theme');

        return is_string($theme) && is_array(config('themes.themes.'.$theme.'.palette')) ? $theme : null;
    }

    /**
     * Themes proposes dans les Reglages : tous sauf ceux de fete.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function selectable(): array
    {
        return array_filter(config('themes.themes', []), fn (array $theme): bool => ! ($theme['festive'] ?? false));
    }
}
