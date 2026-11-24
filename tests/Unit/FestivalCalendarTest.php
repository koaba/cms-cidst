<?php

use App\Services\Festival\FestivalCalendar;

function fcCalendar(): FestivalCalendar
{
    return new FestivalCalendar((require dirname(__DIR__, 2).'/config/festivals.php')['festivals']);
}

function fcOn(string $date): ?string
{
    return fcCalendar()->activeOn(new DateTimeImmutable($date));
}

function fcWindow(string $key, int $year): string
{
    return implode('..', array_map(fn ($d) => $d->format('m-d'), fcCalendar()->windowOf($key, $year)));
}

it('calcule le dimanche de Paques', function (int $year, string $expected) {
    expect(FestivalCalendar::easter($year)->format('Y-m-d'))->toBe($expected);
})->with([
    [2024, '2024-03-31'],
    [2026, '2026-04-05'],
    [2027, '2027-03-28'],
    [2028, '2028-04-16'],
    [2030, '2030-04-21'],
    [2038, '2038-04-25'],
]);

it('place la Pentecote 49 jours apres Paques', function (int $year, string $expected) {
    expect(fcCalendar()->dayOf('pentecote', $year)->format('Y-m-d'))->toBe($expected);
})->with([
    [2026, '2026-05-24'],
    [2027, '2027-05-16'],
]);

it('donne la fenetre de decoration de chaque fete', function (string $key, string $window) {
    expect(fcWindow($key, 2026))->toBe($window);
})->with([
    ['noel', '12-15..12-26'],
    ['nouvelan', '12-27..01-02'],
    ['paques', '03-29..04-06'],
    ['pentecote', '05-21..05-25'],
    ['independance', '06-19..06-27'],
]);

it('donne la fenetre en cours ou la prochaine a venir', function (string $key, string $from, string $expected) {
    [$start, $end] = fcCalendar()->nextWindow($key, new DateTimeImmutable($from));

    expect($start->format('Y-m-d').'..'.$end->format('Y-m-d'))->toBe($expected);
})->with([
    ['noel', '2026-11-24', '2026-12-15..2026-12-26'],
    ['noel', '2026-12-20', '2026-12-15..2026-12-26'],
    ['noel', '2026-12-26', '2026-12-15..2026-12-26'],
    ['noel', '2026-12-27', '2027-12-15..2027-12-26'],
    ['nouvelan', '2026-11-24', '2026-12-27..2027-01-02'],
    ['nouvelan', '2027-01-02', '2026-12-27..2027-01-02'],
    ['nouvelan', '2027-01-03', '2027-12-27..2028-01-02'],
    ['paques', '2026-04-06', '2026-03-29..2026-04-06'],
    ['paques', '2026-04-07', '2027-03-21..2027-03-29'],
]);

it('active la fete sur ses bornes exactes, pas un jour de plus ni de moins', function (string $date, ?string $expected) {
    expect(fcOn($date))->toBe($expected);
})->with([
    ['2026-12-14', null],
    ['2026-12-15', 'noel'],
    ['2026-12-26', 'noel'],
    ['2026-12-27', 'nouvelan'],
    ['2026-12-31', 'nouvelan'],
    ['2027-01-01', 'nouvelan'],
    ['2027-01-02', 'nouvelan'],
    ['2027-01-03', null],
    ['2026-01-02', 'nouvelan'],
    ['2026-01-03', null],
    ['2026-06-18', null],
    ['2026-06-26', 'independance'],
    ['2026-06-28', null],
    ['2026-04-05 23:59:59', 'paques'],
    ['2026-07-15', null],
]);

it('ne chevauche jamais deux fetes de 2026 a 2060', function () {
    $calendar = fcCalendar();
    $keys = array_keys((require dirname(__DIR__, 2).'/config/festivals.php')['festivals']);

    foreach (range(2026, 2060) as $year) {
        foreach ($keys as $a) {
            foreach ($keys as $b) {
                if ($a >= $b) {
                    continue;
                }
                [$aStart, $aEnd] = $calendar->windowOf($a, $year);
                [$bStart, $bEnd] = $calendar->windowOf($b, $year);

                expect($aStart <= $bEnd && $bStart <= $aEnd)->toBeFalse("{$a} chevauche {$b} en {$year}");
            }
        }
    }
});

it('refuse une fete inconnue', function () {
    fcCalendar()->dayOf('inconnue', 2026);
})->throws(InvalidArgumentException::class);
