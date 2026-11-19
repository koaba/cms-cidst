<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Cree le premier compte Super Admin d'une installation vierge.
 * Les roles sont crees ici aussi : en production le seeder n'est jamais lance.
 */
class SuperAdminCreator
{
    public function exists(): bool
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', UserRole::SuperAdmin->value))
            ->exists();
    }

    public function create(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password) {
            foreach (UserRole::cases() as $role) {
                Role::findOrCreate($role->value, 'web');
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'must_change_password' => true,
            ]);
            $user->assignRole(UserRole::SuperAdmin);

            return $user;
        });
    }
}
