<?php

use App\Models\User;

test('l\'ecran d\'inscription publique n\'existe plus', function () {
    $this->get('/register')->assertNotFound();
});

test('l\'inscription publique est fermee et ne cree aucun utilisateur', function () {
    $avant = User::count();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::count())->toBe($avant);
    $this->assertGuest();
});
