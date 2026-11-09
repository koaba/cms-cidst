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
