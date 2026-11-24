<?php

namespace App\Services\Festival;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Calendrier des fetes : classe pure (aucune dependance a Laravel), alimentee par config/festivals.php.
 * Les comparaisons se font sur des jours entiers (Y-m-d) : l'heure n'intervient jamais.
 */
class FestivalCalendar
{
    /** @param  array<string, array<string, mixed>>  $festivals */
    public function __construct(private readonly array $festivals) {}

    /** Dimanche de Paques (calendrier gregorien, algorithme de Meeus/Jones/Butcher). */
    public static function easter(int $year): DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }

    /** Jour meme de la fete pour une annee donnee. */
    public function dayOf(string $key, int $year): DateTimeImmutable
    {
        $rule = $this->festival($key)['rule'] ?? [];

        if (isset($rule['fixed'])) {
            [$month, $day] = $rule['fixed'];

            return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
        }

        if (isset($rule['easter'])) {
            return self::easter($year)->modify(sprintf('%+d days', (int) $rule['easter']));
        }

        throw new InvalidArgumentException("Regle de date inconnue pour la fete « {$key} ».");
    }

    /**
     * Fenetre de decoration [debut, fin] (jours inclus).
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public function windowOf(string $key, int $year): array
    {
        $festival = $this->festival($key);
        $day = $this->dayOf($key, $year);

        return [
            $day->modify(sprintf('-%d days', (int) ($festival['before'] ?? 0))),
            $day->modify(sprintf('+%d days', (int) ($festival['after'] ?? 0))),
        ];
    }

    /**
     * Fenetre en cours ou la prochaine a venir pour une fete, a partir d'un jour donne (jours inclus).
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public function nextWindow(string $key, DateTimeInterface $from): array
    {
        $today = $from->format('Y-m-d');
        $year = (int) $from->format('Y');

        // Annee precedente incluse : la fenetre de fin d'annee finit en janvier.
        foreach ([$year - 1, $year] as $candidate) {
            $window = $this->windowOf($key, $candidate);

            if ($window[1]->format('Y-m-d') >= $today) {
                return $window;
            }
        }

        return $this->windowOf($key, $year + 1);
    }

    /** Cle de la fete decoree ce jour-la, ou null. En cas de chevauchement, la premiere de la config l'emporte. */
    public function activeOn(DateTimeInterface $date): ?string
    {
        $today = $date->format('Y-m-d');
        $year = (int) $date->format('Y');

        foreach (array_keys($this->festivals) as $key) {
            // Annee voisine incluse : une fenetre de fin d'annee peut deborder sur janvier.
            foreach ([$year - 1, $year, $year + 1] as $candidate) {
                [$start, $end] = $this->windowOf($key, $candidate);

                if ($today >= $start->format('Y-m-d') && $today <= $end->format('Y-m-d')) {
                    return $key;
                }
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function festival(string $key): array
    {
        return $this->festivals[$key] ?? throw new InvalidArgumentException("Fete inconnue : {$key}.");
    }
}
