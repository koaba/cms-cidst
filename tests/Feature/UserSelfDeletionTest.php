<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('empeche un Super Admin de supprimer son propre compte', function () {
    if (! Role::where('name', 'Super Admin')->exists()) {
        Role::create(['name' => 'Super Admin']);
    }
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

it('permet a un Super Admin de supprimer un autre utilisateur', function () {
    if (! Role::where('name', 'Super Admin')->exists()) {
        Role::create(['name' => 'Super Admin']);
    }
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');
    $otherUser = User::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $otherUser));

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseMissing('users', ['id' => $otherUser->id]);
});