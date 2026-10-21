<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Periode glissante du tableau de bord, finissant aujourd'hui.
 * week = 7 jours, month = 30 jours, year = 12 mois.
 */
final readonly class DashboardPeriod
{
    private const PERIODS = [
        'week' => ['count' => 7, 'step' => 'day'],
        'month' => ['count' => 30, 'step' => 'day'],
        'year' => ['count' => 12, 'step' => 'month'],
        'all' => ['count' => 12, 'step' => 'month'],
    ];

    public const GLOBAL_MAX_MONTHS = 60;

    public const DEFAULT = 'month';

    private function __construct(public string $key, public string $step, private int $count) {}

    public static function from(?string $key, ?CarbonInterface $firstView = null): self
    {
        $key = array_key_exists((string) $key, self::PERIODS) ? (string) $key : self::DEFAULT;
        $count = self::PERIODS[$key]['count'];

        if ($key === 'all' && $firstView !== null) {
            $months = (int) CarbonImmutable::now()->startOfMonth()->diffInMonths(CarbonImmutable::instance($firstView)->startOfMonth(), true) + 1;
            $count = max(1, min(self::GLOBAL_MAX_MONTHS, $months));
        }

        return new self($key, self::PERIODS[$key]['step'], $count);
    }

    public function isGlobal(): bool
    {
        return $this->key === 'all';
    }

    /** @return array<string, string> cle => libelle */
    public static function options(): array
    {
        return ['week' => '7 jours', 'month' => '30 jours', 'year' => '12 mois', 'all' => 'Global'];
    }

    public function label(): string
    {
        return self::options()[$this->key];
    }

    public function format(): string
    {
        return $this->step === 'month' ? 'Y-m' : 'Y-m-d';
    }

    public function start(): CarbonImmutable
    {
        $today = CarbonImmutable::now();

        return $this->step === 'month'
            ? $today->startOfMonth()->subMonths($this->count - 1)
            : $today->startOfDay()->subDays($this->count - 1);
    }

    public function previousStart(): CarbonImmutable
    {
        return $this->step === 'month'
            ? $this->start()->subMonths($this->count)
            : $this->start()->subDays($this->count);
    }

    /** @return array<string, int> */
    public function labels(): array
    {
        $labels = [];
        $cursor = $this->start();

        for ($i = 0; $i < $this->count; $i++) {
            $labels[$cursor->format($this->format())] = 0;
            $cursor = $this->step === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return $labels;
    }

    public static function variation(int $current, int $previous): ?float
    {
        return $previous === 0 ? null : round(($current - $previous) / $previous * 100, 1);
    }
}
