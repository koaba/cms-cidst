<?php

use App\Models\SiteVisit;
use App\Services\Dashboard\AudienceService;

function visit(string $path, string $hash, ?string $ref = null, string $device = 'desktop', $at = null): void
{
    SiteVisit::create(['path' => $path, 'visitor_hash' => $hash, 'referrer_host' => $ref, 'device' => $device, 'visited_at' => $at ?? now()]);
}

it('compte visites, visiteurs uniques et appareils', function () {
    visit('/blog', 'a');
    visit('/blog', 'a');
    visit('/contact', 'b', null, 'mobile');
    $s = app(AudienceService::class)->summary(now()->subDay());
    expect($s['visits'])->toBe(3);
    expect($s['visitors'])->toBe(2);
    expect($s['devices'])->toBe(['desktop' => 2, 'mobile' => 1]);
});

it('classe les pages et les references par frequence', function () {
    visit('/blog', 'a', 'x.test');
    visit('/blog', 'b', 'x.test');
    visit('/contact', 'c', 'y.test');
    visit('/pages', 'd');
    $s = app(AudienceService::class)->summary(now()->subDay());
    expect(array_key_first($s['top_pages']))->toBe('/blog');
    expect($s['top_pages']['/blog'])->toBe(2);
    expect($s['referrers'])->toBe(['x.test' => 2, 'y.test' => 1]);
});

it('ignore les visites hors de la periode', function () {
    visit('/blog', 'a', null, 'desktop', now()->subDays(10));
    visit('/blog', 'b');
    $s = app(AudienceService::class)->summary(now()->subDays(2));
    expect($s['visits'])->toBe(1);
    expect($s['visitors'])->toBe(1);
});
