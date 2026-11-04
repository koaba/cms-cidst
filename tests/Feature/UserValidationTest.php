<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = adminUser();
    publicationUser();
});

it('refuse la creation avec un mot de passe trop court', function () {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Nouveau', 'email' => 'nouveau@example.org', 'password' => 'court', 'role' => 'Publication',
    ])->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'nouveau@example.org']);
});

it('refuse la creation avec un e-mail deja utilise', function () {
    $existing = User::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Doublon', 'email' => $existing->email, 'password' => 'motdepasse', 'role' => 'Publication',
    ])->assertSessionHasErrors('email');
});

it('refuse la modification avec l e-mail d un autre utilisateur', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
        'name' => $user->name, 'email' => $other->email, 'role' => 'Publication',
    ])->assertSessionHasErrors('email');
});

it('autorise la modification en gardant son propre e-mail', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
        'name' => 'Nom change', 'email' => $user->email, 'role' => 'Publication',
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

    expect($user->fresh()->name)->toBe('Nom change');
});

it('ignore un mot de passe envoye a la modification', function () {
    $user = User::factory()->create(['password' => Hash::make('ancien-mot-de-passe')]);

    $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'role' => 'Publication', 'password' => 'pirate-12345',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('ancien-mot-de-passe', $user->fresh()->password))->toBeTrue();
});
