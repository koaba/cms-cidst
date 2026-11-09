<?php

use App\Models\SiteVisit;
use Illuminate\Support\Facades\DB;

function oldVisit(string $hash, string $device, $at): void
{
    SiteVisit::create(['path' => '/blog', 'visitor_hash' => $hash, 'referrer_host' => null, 'device' => $device, 'visited_at' => $at]);
}

it('resume par jour puis supprime le detail de plus de 13 mois', function () {
    $old = now()->subMonths(14)->startOfDay()->addHours(10);
    oldVisit('a', 'desktop', $old);
    oldVisit('b', 'mobile', $old);
    oldVisit('b', 'mobile', $old);
    oldVisit('c', 'desktop', now()->subMonths(6));
    oldVisit('d', 'desktop', now());

    $this->artisan('audience:prune')->assertSuccessful();

    expect(SiteVisit::count())->toBe(2);
    $row = DB::table('site_visit_daily')->first();
    expect($row->day)->toBe($old->toDateString());
    expect([(int) $row->visits, (int) $row->visitors, (int) $row->mobile_visits])->toBe([3, 2, 2]);
});

it('ne compte rien en double quand la commande est relancee', function () {
    $old = now()->subMonths(14);
    oldVisit('a', 'desktop', $old);
    $this->artisan('audience:prune')->assertSuccessful();
    $this->artisan('audience:prune')->assertSuccessful();
    expect(DB::table('site_visit_daily')->count())->toBe(1);
    expect((int) DB::table('site_visit_daily')->value('visits'))->toBe(1);
});

it('est planifiee chaque jour', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('audience:prune')->assertSuccessful();
});
