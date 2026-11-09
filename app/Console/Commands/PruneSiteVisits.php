<?php

namespace App\Console\Commands;

use App\Models\SiteVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneSiteVisits extends Command
{
    protected $signature = 'audience:prune';

    protected $description = 'Resume par jour les visites de plus de 13 mois puis supprime le detail';

    public function handle(): int
    {
        $cutoff = now()->subMonths(13)->startOfDay();

        $days = DB::table('site_visits')->where('visited_at', '<', $cutoff)
            ->selectRaw("DATE(visited_at) as day, COUNT(*) as visits, COUNT(DISTINCT visitor_hash) as visitors, SUM(CASE WHEN device = 'mobile' THEN 1 ELSE 0 END) as mobile_visits")
            ->groupByRaw('DATE(visited_at)')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'visits' => (int) $row->visits,
                'visitors' => (int) $row->visitors,
                'mobile_visits' => (int) $row->mobile_visits,
            ])->all();

        DB::transaction(function () use ($days, $cutoff) {
            if ($days !== []) {
                DB::table('site_visit_daily')->upsert($days, ['day'], ['visits', 'visitors', 'mobile_visits']);
            }
            SiteVisit::where('visited_at', '<', $cutoff)->delete();
        });

        $this->info(count($days).' jour(s) resume(s).');

        return self::SUCCESS;
    }
}
