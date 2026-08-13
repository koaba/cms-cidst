<?php

use App\Models\User;

it('force le changement de mot de passe pour un utilisateur marque must_change_password', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    expect($user->fresh()->must_change_password)->toBeTrue();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertRedirect(route('profile.edit'));
});

it('ne boucle pas indefiniment sur la page profil', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
});

it('libere l’utilisateur apres changement de mot de passe', function () {
    $user = User::factory()->create([
        'must_change_password' => true,
    ]);

    $this->actingAs($user)->put(route('profile.password.update') ?? route('password.update'), [
        'current_password' => 'password',
        'password' => 'nouveaumotdepasse123',
        'password_confirmation' => 'nouveaumotdepasse123',
    ]);

    expect($user->fresh()->must_change_password)->toBeFalse();
});