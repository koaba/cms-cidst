<?php

use App\Models\User;

/**
 * Vrai si un meme <form> porte cette action ET cette methode (_method).
 * Necessaire : profile.update et profile.destroy partagent l'URL /profile.
 */
function prfFormHasMethod(string $html, string $action, string $method): bool
{
    $pattern = '#<form[^>]*action="'.preg_quote($action, '#').'"[^>]*>(?:(?!</form>).)*?name="_method" value="'.$method.'"#s';

    return preg_match($pattern, $html) === 1;
}

test('la page de profil s\'affiche', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();

    $html = $response->getContent();
    expect(prfFormHasMethod($html, route('profile.update'), 'patch'))->toBeTrue();
    expect(prfFormHasMethod($html, route('password.update'), 'put'))->toBeTrue();
    expect(prfFormHasMethod($html, route('profile.destroy'), 'delete'))->toBeTrue();
});

test('les informations du profil peuvent être mises à jour', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('le statut de vérification de l\'email reste inchangé si l\'adresse email ne change pas', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('un utilisateur peut supprimer son compte', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('le mot de passe correct doit être fourni pour supprimer le compte', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
