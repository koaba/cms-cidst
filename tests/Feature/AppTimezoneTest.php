<?php

use App\Models\Page;
use App\Models\SiteVisit;
use Carbon\CarbonImmutable;

// Madagascar : UTC+3 toute l annee, sans heure d ete.

it('utilise le fuseau de Madagascar', function () {
    expect(config('app.timezone'))->toBe('Indian/Antananarivo')
        ->and(now()->format('P'))->toBe('+03:00');
});

it('change de jour d audience a minuit heure de Madagascar', function () {
    // 20h30 UTC = 23h30 a Madagascar ; 21h30 UTC = 00h30 le lendemain.
    $this->travelTo(CarbonImmutable::parse('2026-11-09 20:30:00', 'UTC'));
    $this->get('/contact')->assertOk();
    $this->travelTo(CarbonImmutable::parse('2026-11-09 21:30:00', 'UTC'));
    $this->get('/contact')->assertOk();

    expect(SiteVisit::pluck('visitor_hash')->unique())->toHaveCount(2);
    $this->travelBack();
});

it('publie une page programmee a l heure de Madagascar', function (string $maintenantUtc, string $statut) {
    $page = Page::factory()->create(['is_published' => true, 'published_at' => '2026-11-09 14:30:00']);

    $this->travelTo(CarbonImmutable::parse($maintenantUtc, 'UTC'));

    expect($page->fresh()->status)->toBe($statut);
    $this->travelBack();
})->with([
    'avant 14h30 locale' => ['2026-11-09 11:00:00', 'scheduled'],
    'apres 14h30 locale' => ['2026-11-09 11:31:00', 'published'],
]);
