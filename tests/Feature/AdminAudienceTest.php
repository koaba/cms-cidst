<?php

use App\Enums\UserRole;
use App\Models\SiteVisit;

it('redirige un visiteur non connecte', function () {
    $this->get('/admin/audience')->assertRedirect();
});

it('refuse la page a Publication', function () {
    $this->actingAs(publicationUser())->get('/admin/audience')->assertForbidden();
});

it('affiche les chiffres au Super Admin', function () {
    foreach ([['a', '/blog', 'x.test'], ['b', '/blog', null]] as [$hash, $path, $ref]) {
        SiteVisit::create(['path' => $path, 'visitor_hash' => $hash, 'referrer_host' => $ref, 'device' => 'desktop', 'visited_at' => now()]);
    }
    $this->actingAs(adminUser())->get('/admin/audience')->assertOk()
        ->assertSee('data-audience="visitors" data-count="2"', false)
        ->assertSee('data-audience="visits" data-count="2"', false)
        ->assertSee('/blog')
        ->assertSee('x.test');
});

it('retombe sur 30 jours quand la periode est invalide', function () {
    $this->actingAs(adminUser())->get('/admin/audience?period=zzz')->assertOk();
});

it('declare l entree de menu reservee au Super Admin', function () {
    $item = collect(config('admin_menu'))->firstWhere('route', 'admin.audience');
    expect($item)->not->toBeNull();
    expect($item['roles'] ?? [])->toBe([UserRole::SuperAdmin->value]);
    expect($item['description'] ?? '')->not->toBeEmpty();
});

it('compte sur 7 jours aujourd hui et les 6 jours precedents, pas le 7e', function () {
    $this->travelTo(now()->startOfDay()->addHours(12));
    $day = fn (int $ago) => now()->startOfDay()->subDays($ago);

    foreach ([['a', '/aujourdhui', $day(0)->addHour()], ['b', '/jour-6', $day(6)->addMinute()], ['c', '/jour-7', $day(7)->endOfDay()]] as [$hash, $path, $at]) {
        SiteVisit::create(['path' => $path, 'visitor_hash' => $hash, 'device' => 'desktop', 'visited_at' => $at]);
    }

    $this->actingAs(adminUser())->get('/admin/audience?period=week')->assertOk()
        ->assertSee('data-audience="visits" data-count="2"', false)
        ->assertSee('/jour-6')
        ->assertDontSee('/jour-7');
});

it('retombe sur 30 jours pour period=all et ne propose pas Global', function () {
    foreach ([['a', '/recent', 20], ['b', '/ancien', 40]] as [$hash, $path, $ago]) {
        SiteVisit::create(['path' => $path, 'visitor_hash' => $hash, 'device' => 'desktop', 'visited_at' => now()->subDays($ago)]);
    }

    $this->actingAs(adminUser())->get('/admin/audience?period=all')->assertOk()
        ->assertSee('data-audience="visits" data-count="1"', false)
        ->assertDontSee('/ancien')
        ->assertDontSee('period=all', false);
});
