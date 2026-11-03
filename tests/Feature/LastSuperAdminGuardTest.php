<?php

use App\Models\User;

it('refuse la suppression du compte du dernier Super Admin via le profil', function () {
    $admin = adminUser();

    $response = $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password']);

    $response->assertSessionHasErrors('password', errorBag: 'userDeletion');
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
    $this->assertAuthenticatedAs($admin);
});

it('autorise la suppression du compte d un Super Admin quand un autre existe', function () {
    $admin = adminUser();
    adminUser();

    $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertDatabaseMissing('users', ['id' => $admin->id]);
});

it('autorise un compte Publication a supprimer son propre compte', function () {
    adminUser();
    $publication = publicationUser();

    $this->actingAs($publication)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertDatabaseMissing('users', ['id' => $publication->id]);
});

it('refuse de retrograder le dernier Super Admin', function () {
    $admin = adminUser();
    publicationUser();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'Publication',
    ]);

    $response->assertSessionHasErrors('role');
    expect(User::find($admin->id)->hasRole('Super Admin'))->toBeTrue();
});

it('autorise la retrogradation d un Super Admin quand un autre existe', function () {
    $admin = adminUser();
    $other = adminUser();
    publicationUser();

    $this->actingAs($admin)->put(route('admin.users.update', $other), [
        'name' => $other->name,
        'email' => $other->email,
        'role' => 'Publication',
    ])->assertRedirect(route('admin.users.index'));

    expect(User::find($other->id)->hasRole('Publication'))->toBeTrue();
});

it('autorise le dernier Super Admin a modifier son nom en gardant son role', function () {
    $admin = adminUser();

    $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => 'Nouveau Nom',
        'email' => $admin->email,
        'role' => 'Super Admin',
    ])->assertRedirect(route('admin.users.index'));

    expect(User::find($admin->id)->name)->toBe('Nouveau Nom');
});
