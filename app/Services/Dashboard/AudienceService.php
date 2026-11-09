<?php

namespace App\Services\Dashboard;

use App\Models\SiteVisit;
use DateTimeInterface;

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

    /** @return array<string,int> */
    private function ranking($query, string $column, int $limit): array
    {
        return $query->selectRaw("{$column} as label, COUNT(*) as n")
            ->groupBy($column)->orderByDesc('n')->orderBy($column)->limit($limit)
            ->pluck('n', 'label')->map(fn ($n) => (int) $n)->all();
    }
}
