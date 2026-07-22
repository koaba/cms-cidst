<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $role = Role::firstOrCreate(['name' => 'Super Admin']);

        // Assigner ce rôle à tous les utilisateurs qui avaient déjà
        // le rôle "Super Admin" dans l'ancien système custom
        $admins = User::whereHas('role', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();

        foreach ($admins as $admin) {
            $admin->assignRole($role);
        }
    }
}