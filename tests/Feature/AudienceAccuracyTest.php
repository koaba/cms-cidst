<?php

use App\Models\SiteVisit;

it('ignore les utilisateurs connectes sur le site public', function () {
    $this->actingAs(adminUser())->get('/contact')->assertOk();
    expect(SiteVisit::count())->toBe(0);
});

it('compte toujours les visiteurs anonymes', function () {
    $this->get('/contact')->assertOk();
    expect(SiteVisit::count())->toBe(1);
});

it('precise que les visiteurs sont cumules par jour', function () {
    $this->actingAs(adminUser())->get('/admin/audience')->assertOk()
        ->assertSee('Visiteurs (cumul des jours)')
        ->assertDontSee('Visiteurs uniques');
});
