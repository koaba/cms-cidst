<?php

test('l\'écran d\'inscription peut s\'afficher', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('de nouveaux utilisateurs peuvent s\'inscrire', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
