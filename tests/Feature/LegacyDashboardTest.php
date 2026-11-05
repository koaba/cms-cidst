<?php

it('redirige l ancienne route dashboard vers le tableau de bord admin', function () {
    $this->actingAs(adminUser())->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

it('ne garde plus la vue Breeze dashboard inutilisee', function () {
    expect(view()->exists('dashboard'))->toBeFalse();
});
