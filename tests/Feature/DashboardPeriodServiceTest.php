<?php

use App\Services\Dashboard\DashboardPeriod;

function periodOrNull(?string $key): ?DashboardPeriod
{
    try {
        return DashboardPeriod::from($key);
    } catch (Throwable) {
        return null;
    }
}

function variationOrNull(int $current, int $previous): ?float
{
    try {
        return DashboardPeriod::variation($current, $previous);
    } catch (Throwable) {
        return -999.0;
    }
}

it('retombe sur month pour une valeur absente ou inconnue', function (?string $key) {
    expect(periodOrNull($key)?->key)->toBe('month');
})->with(['null' => null, 'vide' => '', 'inconnue' => 'decade', 'injection' => "week' OR 1=1"]);

it('accepte week, month et year', function (string $key) {
    expect(periodOrNull($key)?->key)->toBe($key);
})->with(['week', 'month', 'year']);

it('fournit le bon nombre de libelles et le bon pas', function (string $key, int $count, string $step) {
    $period = periodOrNull($key);

    expect($period?->labels() ?? [])->toHaveCount($count)
        ->and($period?->step)->toBe($step);
})->with([
    'week' => ['week', 7, 'day'],
    'month' => ['month', 30, 'day'],
    'year' => ['year', 12, 'month'],
]);

it('termine les libelles sur aujourd hui ou le mois courant', function () {
    expect(array_key_last(periodOrNull('week')?->labels() ?? []))->toBe(now()->format('Y-m-d'))
        ->and(array_key_last(periodOrNull('year')?->labels() ?? []))->toBe(now()->format('Y-m'));
});

it('calcule une variation en pourcentage, null si la periode precedente est vide', function () {
    expect(variationOrNull(15, 10))->toBe(50.0)
        ->and(variationOrNull(5, 10))->toBe(-50.0)
        ->and(variationOrNull(5, 0))->toBeNull();
});

function globalPeriodOrNull($first = null): ?DashboardPeriod
{
    try {
        return DashboardPeriod::from('all', $first);
    } catch (Throwable) {
        return null;
    }
}

it('accepte all comme periode globale', function () {
    expect(periodOrNull('all')?->key)->toBe('all');
});

it('couvre les mois depuis la premiere vue, plafonnes a 60', function (int $monthsAgo, int $expected) {
    $labels = globalPeriodOrNull(now()->startOfMonth()->subMonths($monthsAgo))?->labels() ?? [];

    expect($labels)->toHaveCount($expected)
        ->and(array_key_last($labels))->toBe(now()->format('Y-m'));
})->with([
    'ce mois' => [0, 1],
    '5 mois' => [5, 6],
    '10 ans' => [120, 60],
]);

it('retombe sur 12 mois sans aucune vue', function () {
    expect(globalPeriodOrNull(null)?->labels() ?? [])->toHaveCount(12);
});
