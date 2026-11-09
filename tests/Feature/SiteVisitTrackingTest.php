<?php

use App\Models\SiteVisit;

it('enregistre une visite sur une page publique sans la requete', function () {
    $this->withHeaders(['Referer' => 'https://exemple.test/a?b=1'])->get('/contact?x=secret')->assertOk();
    expect(SiteVisit::count())->toBe(1);
    $visit = SiteVisit::first();
    expect($visit->path)->toBe('/contact');
    expect($visit->referrer_host)->toBe('exemple.test');
    expect($visit->device)->toBe('desktop');
    expect($visit->visitor_hash)->toHaveLength(64);
});

it('detecte les mobiles', function () {
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'])->get('/contact')->assertOk();
    expect(SiteVisit::first()?->device)->toBe('mobile');
});

it('ignore les robots', function () {
    $this->get('/contact')->assertOk();
    $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->get('/contact')->assertOk();
    expect(SiteVisit::count())->toBe(1);
});

it('ignore l administration', function () {
    $this->get('/contact')->assertOk();
    $this->actingAs(adminUser())->get('/admin')->assertOk();
    expect(SiteVisit::count())->toBe(1);
});

it('change de hash visiteur chaque jour mais pas dans la journee', function () {
    $this->travelTo(now()->startOfDay()->addHours(10));
    $this->get('/contact')->assertOk();
    $this->get('/contact')->assertOk();
    $this->travelTo(now()->addDay());
    $this->get('/contact')->assertOk();
    expect(SiteVisit::count())->toBe(3);
    expect(SiteVisit::pluck('visitor_hash')->unique())->toHaveCount(2);
    $this->travelBack();
});
