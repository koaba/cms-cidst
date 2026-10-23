<?php

use App\Models\User;

it('masque les statistiques a un utilisateur Publication', function () {
    User::factory()->create(['name' => 'Zorglub Visible']);

    $html = $this->actingAs(publicationUser())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)
        ->not->toContain('window.dashboardActivity')
        ->not->toContain('window.dashboardViews')
        ->not->toContain('Derniers utilisateurs')
        ->not->toContain('Zorglub Visible')
        ->not->toContain('Poids total');
});

it('ne calcule pas les statistiques pour Publication', function () {
    $this->actingAs(publicationUser())->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewMissing('stats');
});

it('garde les statistiques pour Super Admin', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)
        ->toContain('window.dashboardActivity')
        ->toContain('Derniers utilisateurs');
});
