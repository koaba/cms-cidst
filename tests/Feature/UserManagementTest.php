<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('cree un utilisateur avec son role et impose le changement de mot de passe', function () {
    $admin = adminUser();
    publicationUser();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Nouvelle Redactrice',
        'email' => 'redactrice@example.com',
        'password' => 'motdepasse1',
        'role' => 'Publication',
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $created = User::where('email', 'redactrice@example.com')->first();
    expect($created)->not->toBeNull()
        ->and($created->hasRole('Publication'))->toBeTrue()
        ->and($created->must_change_password)->toBeTrue()
        ->and(Hash::check('motdepasse1', $created->password))->toBeTrue();
});

it('refuse la creation avec un role inconnu par une erreur de validation, pas un 500', function () {
    $admin = adminUser();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'X',
        'email' => 'x@example.com',
        'password' => 'motdepasse1',
        'role' => 'Inexistant',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
});

it('met a jour le nom, l email et le role d un utilisateur', function () {
    $admin = adminUser();
    $target = publicationUser();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
        'name' => 'Nom Modifie',
        'email' => 'modifie@example.com',
        'role' => 'Super Admin',
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $target->refresh();
    expect($target->name)->toBe('Nom Modifie')
        ->and($target->email)->toBe('modifie@example.com')
        ->and($target->hasRole('Super Admin'))->toBeTrue()
        ->and($target->hasRole('Publication'))->toBeFalse();
});

it('refuse la mise a jour avec un role inconnu', function () {
    $admin = adminUser();
    $target = publicationUser();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => 'Inexistant',
    ]);

    $response->assertSessionHasErrors('role');
    expect($target->fresh()->hasRole('Publication'))->toBeTrue();
});

it('reinitialise le mot de passe d un autre utilisateur et force son changement', function () {
    $admin = adminUser();
    $target = publicationUser();

    $this->actingAs($admin)
        ->patch(route('admin.users.reset-password', $target), ['new_password' => 'nouveau-mdp-1'])
        ->assertSessionHas('success');

    $target->refresh();
    expect(Hash::check('nouveau-mdp-1', $target->password))->toBeTrue()
        ->and($target->must_change_password)->toBeTrue();
});

it('refuse de reinitialiser son propre mot de passe depuis l administration', function () {
    $admin = adminUser();
    $oldHash = $admin->password;

    $this->actingAs($admin)
        ->patch(route('admin.users.reset-password', $admin), ['new_password' => 'nouveau-mdp-1'])
        ->assertSessionHas('error');

    expect($admin->fresh()->password)->toBe($oldHash);
});

it('interdit la gestion des utilisateurs au role Publication', function () {
    $publication = publicationUser();

    $this->actingAs($publication)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($publication)->post(route('admin.users.store'), [
        'name' => 'Y', 'email' => 'y@example.com', 'password' => 'motdepasse1', 'role' => 'Publication',
    ])->assertForbidden();
});
