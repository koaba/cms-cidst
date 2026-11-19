<?php

use App\Models\SiteVisit;

it('ne compte pas un visiteur qui envoie DNT 1', function () {
    $this->withHeaders(['DNT' => '1'])->get('/contact')->assertOk();

    expect(SiteVisit::count())->toBe(0);
});

it('ne compte pas un visiteur qui envoie Sec-GPC 1', function () {
    $this->withHeaders(['Sec-GPC' => '1'])->get('/contact')->assertOk();

    expect(SiteVisit::count())->toBe(0);
});

it('compte un visiteur dont les signaux DNT et Sec-GPC valent 0', function () {
    $this->withHeaders(['DNT' => '0', 'Sec-GPC' => '0'])->get('/contact')->assertOk();

    expect(SiteVisit::count())->toBe(1);
});

it('signe le hash visiteur avec HMAC et APP_KEY', function () {
    $this->travelTo(now()->setTime(12, 0));

    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Test'])
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->get('/contact')
        ->assertOk();

    expect(SiteVisit::query()->value('visitor_hash'))
        ->toBe(hash_hmac('sha256', '203.0.113.7|Mozilla/5.0 Test|'.now()->toDateString(), config('app.key')));
});
