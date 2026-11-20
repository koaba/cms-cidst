<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('l\'écran de lien de réinitialisation du mot de passe peut s\'afficher', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('action="'.route('password.email').'"', false);
});

test('un lien de réinitialisation du mot de passe peut être demandé', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('l\'écran de réinitialisation du mot de passe peut s\'afficher', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $this->get('/reset-password/'.$notification->token)
            ->assertOk()
            ->assertSee('action="'.route('password.store').'"', false)
            ->assertSee('name="token" value="'.$notification->token.'"', false);

        return true;
    });
});

test('le mot de passe peut être réinitialisé avec un jeton valide', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
