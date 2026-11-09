<?php

use App\Models\SiteVisit;
use App\Services\Dashboard\AudienceService;
use Illuminate\Support\Facades\DB;

function dailyRow(string $day, int $visits, int $visitors, int $mobile): void
{
    DB::table('site_visit_daily')->insert(['day' => $day, 'visits' => $visits, 'visitors' => $visitors, 'mobile_visits' => $mobile]);
}

it('additionne par mois les totaux conserves', function () {
    dailyRow('2025-01-10', 10, 4, 3);
    dailyRow('2025-01-20', 5, 2, 1);
    dailyRow('2025-02-03', 7, 3, 0);
    $rows = app(AudienceService::class)->monthly();
    expect($rows['2025-01'])->toBe(['visits' => 15, 'visitors' => 6, 'mobile' => 4]);
    expect($rows['2025-02'])->toBe(['visits' => 7, 'visitors' => 3, 'mobile' => 0]);
});

it('ajoute les jours recents pas encore resumes', function () {
    $day = now()->subDays(3)->startOfDay()->addHours(9);
    foreach ([['a', 'desktop'], ['a', 'desktop'], ['b', 'mobile']] as [$hash, $device]) {
        SiteVisit::create(['path' => '/blog', 'visitor_hash' => $hash, 'referrer_host' => null, 'device' => $device, 'visited_at' => $day]);
    }
    $rows = app(AudienceService::class)->monthly();
    expect($rows[$day->format('Y-m')])->toBe(['visits' => 3, 'visitors' => 2, 'mobile' => 1]);
});

it('ne compte pas deux fois un jour present dans les deux sources', function () {
    $day = now()->subDays(2)->startOfDay()->addHours(9);
    SiteVisit::create(['path' => '/blog', 'visitor_hash' => 'a', 'referrer_host' => null, 'device' => 'desktop', 'visited_at' => $day]);
    dailyRow($day->toDateString(), 1, 1, 0);
    $rows = app(AudienceService::class)->monthly();
    expect($rows[$day->format('Y-m')]['visits'])->toBe(1);
});

it('affiche l historique mensuel au Super Admin', function () {
    dailyRow('2025-03-05', 12, 5, 2);
    $this->actingAs(adminUser())->get('/admin/audience')->assertOk()
        ->assertSee('id="historique"', false)
        ->assertSee('data-month="2025-03"', false);
});
