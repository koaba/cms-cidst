<?php

use App\Models\User;

test('l\'écran de connexion peut s\'afficher', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('les utilisateurs peuvent s\'authentifier via l\'écran de connexion', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('les utilisateurs ne peuvent pas s\'authentifier avec un mot de passe invalide', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('les utilisateurs peuvent se déconnecter', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
