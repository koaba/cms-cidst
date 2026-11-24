<?php

namespace App\Services\Festival;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use DateTimeInterface;

/**
 * Reglages des fetes modifiables dans Administration > Fetes (lot 3e-bis-4).
 *
 * Source unique pour FestivalTheme et FestivalDecor : interrupteur general, fete active, date du jour
 * (simulee pour le seul Super Admin) et options de chaque fete. Les valeurs par defaut viennent de
 * config/festivals.php ; rien de ce qui est lu en base ou saisi n'est cru sans passer par la liste blanche.
 */
final class FestivalSettings
{
    /** Emplacements possibles du decor : cle => libelle. */
    public const PLACEMENTS = [
        'both' => 'Bandeau et logo',
        'strip' => 'Bandeau seulement',
        'logo' => 'Logo seulement',
        'none' => "Aucun d\u{e9}cor (couleurs du th\u{e8}me seules)",
    ];

    /** Nombre d'icones du bandeau (la liste choisie est repetee en cycle jusque-la). */
    public const STRIP_SLOTS = 7;

    public function __construct(private readonly FestivalCalendar $calendar) {}

    /** Le .env (FESTIVALS_ENABLED) reste un coupe-circuit prioritaire sur l'interrupteur de l'administration. */
    public function enabled(): bool
    {
        return (bool) config('festivals.enabled', true) && $this->switchedOn();
    }

    /**
     * Interrupteur de l'administration seul (sans le .env) : celui que la page Fetes affiche et enregistre.
     * Colonne absente (reglages mis en cache avant la migration) = active, comme la valeur par defaut.
     */
    public function switchedOn(): bool
    {
        return (bool) (SiteSetting::current()->getAttribute('festivals_enabled') ?? true);
    }

    /** Date simulee enregistree (qu'elle s'applique ou non a l'utilisateur courant). */
    public function simulatedOn(): ?DateTimeInterface
    {
        return SiteSetting::current()->festivals_simulated_on;
    }

    /** Jour a utiliser pour les fetes : la date simulee SI l'utilisateur connecte est Super Admin, sinon aujourd'hui. */
    public function today(): DateTimeInterface
    {
        $simulated = $this->simulatedOn();

        return $simulated !== null && $this->isSuperAdmin() ? $simulated : now();
    }

    /** Cle de la fete decoree ce jour-la (interrupteur, fete activee et date pris en compte), ou null. */
    public function activeKey(?DateTimeInterface $date = null): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $key = $this->calendar->activeOn($date ?? $this->today());

        return $key !== null && $this->options($key)['enabled'] ? $key : null;
    }

    /**
     * Options normalisees d'une fete (valeurs enregistrees, completees par celles de la config).
     *
     * @return array{enabled: bool, placement: string, logo: string, strip: list<string>, animated: bool}
     */
    public function options(string $festival): array
    {
        $stored = SiteSetting::current()->festivals_options;
        $raw = is_array($stored) && is_array($stored[$festival] ?? null) ? $stored[$festival] : [];

        return $this->normalizeOne($festival, $raw);
    }

    /**
     * Icones du bandeau d'une fete : la liste choisie, repetee en cycle jusqu'a STRIP_SLOTS.
     *
     * @return list<string>
     */
    public function strip(string $festival): array
    {
        $icons = $this->options($festival)['strip'];

        return array_map(fn (int $i): string => $icons[$i % count($icons)], range(0, self::STRIP_SLOTS - 1));
    }

    /**
     * Saisie du formulaire -> options enregistrables : liste blanche des fetes, emplacements et icones.
     * Une valeur absente ou invalide retombe sur la valeur par defaut de la config.
     *
     * @param  array<array-key, mixed>  $input
     * @return array<string, array{enabled: bool, placement: string, logo: string, strip: list<string>, animated: bool}>
     */
    public function normalize(array $input): array
    {
        $options = [];

        foreach (array_keys((array) config('festivals.festivals', [])) as $festival) {
            if (is_array($input[$festival] ?? null)) {
                $options[$festival] = $this->normalizeOne((string) $festival, $input[$festival]);
            }
        }

        return $options;
    }

    /**
     * Enregistre les reglages valides de la page Fetes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        SiteSetting::current()->update([
            'festivals_enabled' => (bool) ($data['festivals_enabled'] ?? true),
            'festivals_simulated_on' => ($data['festivals_simulated_on'] ?? null) ?: null,
            'festivals_options' => $this->normalize((array) ($data['festivals'] ?? [])),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $raw
     * @return array{enabled: bool, placement: string, logo: string, strip: list<string>, animated: bool}
     */
    private function normalizeOne(string $festival, array $raw): array
    {
        $defaults = $this->defaults($festival);
        $icons = array_map('strval', array_keys((array) config('festivals.icons', [])));
        $isIcon = fn (mixed $icon): bool => is_string($icon) && in_array($icon, $icons, true);

        $logo = $raw['logo'] ?? null;
        $placement = $raw['placement'] ?? null;
        $strip = array_values(array_filter(is_array($raw['strip'] ?? null) ? $raw['strip'] : [], $isIcon));

        return [
            'enabled' => $this->bool($raw['enabled'] ?? null, $defaults['enabled']),
            'placement' => is_string($placement) && isset(self::PLACEMENTS[$placement]) ? $placement : $defaults['placement'],
            'logo' => $isIcon($logo) ? $logo : $defaults['logo'],
            'strip' => $strip === [] ? $defaults['strip'] : array_slice($strip, 0, self::STRIP_SLOTS),
            'animated' => $this->bool($raw['animated'] ?? null, $defaults['animated']),
        ];
    }

    /**
     * Valeurs par defaut d'une fete : tout actif, bandeau et logo, icones du decor de config/festivals.php.
     *
     * @return array{enabled: bool, placement: string, logo: string, strip: list<string>, animated: bool}
     */
    private function defaults(string $festival): array
    {
        $decor = config("festivals.festivals.$festival.decor");
        $set = is_string($decor) ? config("festivals.decors.$decor") : null;
        $first = (string) array_key_first((array) config('festivals.icons', []));
        $strip = is_array($set) && is_array($set['strip'] ?? null) && $set['strip'] !== [] ? array_values($set['strip']) : [$first];

        return [
            'enabled' => true,
            'placement' => 'both',
            'logo' => is_array($set) && is_string($set['logo'] ?? null) ? $set['logo'] : $first,
            'strip' => $strip,
            'animated' => true,
        ];
    }

    private function bool(mixed $value, bool $default): bool
    {
        return $value === null ? $default : (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default);
    }

    private function isSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole(UserRole::SuperAdmin->value);
    }
}
