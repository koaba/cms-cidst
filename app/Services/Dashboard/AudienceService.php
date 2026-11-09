<?php

namespace App\Services\Dashboard;

use App\Models\SiteVisit;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class AudienceService
{
    /** @return array{visits:int, visitors:int, top_pages:array<string,int>, referrers:array<string,int>, devices:array<string,int>} */
    public function summary(DateTimeInterface $since, int $limit = 10): array
    {
        $base = fn () => SiteVisit::query()->where('visited_at', '>=', $since);

        return [
            'visits' => $base()->count(),
            'visitors' => (int) $base()->selectRaw('COUNT(DISTINCT visitor_hash) as n')->value('n'),
            'top_pages' => $this->ranking($base(), 'path', $limit),
            'referrers' => $this->ranking($base()->whereNotNull('referrer_host'), 'referrer_host', $limit),
            'devices' => $this->ranking($base(), 'device', 2),
        ];
    }

    /**
     * Totaux par mois : jours deja resumes (site_visit_daily) puis jours recents
     * encore detailles (site_visits). Un jour present dans les deux est compte une fois.
     * Les visiteurs sont cumules jour par jour : une personne qui revient compte chaque jour.
     *
     * @return array<string, array{visits:int, visitors:int, mobile:int}>
     */
    public function monthly(): array
    {
        $days = [];

        foreach (DB::table('site_visit_daily')->get() as $row) {
            $days[$row->day] = [(int) $row->visits, (int) $row->visitors, (int) $row->mobile_visits];
        }

        $recent = SiteVisit::query()
            ->selectRaw("DATE(visited_at) as day, COUNT(*) as visits, COUNT(DISTINCT visitor_hash) as visitors, SUM(CASE WHEN device = 'mobile' THEN 1 ELSE 0 END) as mobile_visits")
            ->groupByRaw('DATE(visited_at)')->get();

        foreach ($recent as $row) {
            $days[$row->day] = [(int) $row->visits, (int) $row->visitors, (int) $row->mobile_visits];
        }

        $months = [];
        foreach ($days as $day => [$visits, $visitors, $mobile]) {
            $key = substr((string) $day, 0, 7);
            $months[$key] ??= ['visits' => 0, 'visitors' => 0, 'mobile' => 0];
            $months[$key]['visits'] += $visits;
            $months[$key]['visitors'] += $visitors;
            $months[$key]['mobile'] += $mobile;
        }
        krsort($months);

        return $months;
    }

    /** @return array<string,int> */
    private function ranking($query, string $column, int $limit): array
    {
        return $query->selectRaw("{$column} as label, COUNT(*) as n")
            ->groupBy($column)->orderByDesc('n')->orderBy($column)->limit($limit)
            ->pluck('n', 'label')->map(fn ($n) => (int) $n)->all();
    }
}
