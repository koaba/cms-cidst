<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer le rôle Super Admin côté Spatie
        $role = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value]);

        // Assigner ce rôle à tous les utilisateurs qui avaient déjà
        // le rôle "Super Admin" dans l'ancien système custom
        $admins = User::whereHas('role', function ($query) {
            $query->where('name', UserRole::SuperAdmin->value);
        })->get();

        foreach ($admins as $admin) {
            $admin->assignRole($role);
        }
    }
}
