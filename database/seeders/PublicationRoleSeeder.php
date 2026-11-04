<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PublicationRoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'articles.manage',
            'pages.manage',
            'sliders.manage',
            'news-tickers.manage',
            'media.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Permission::firstOrCreate(['name' => 'settings.manage']);

        $publication = Role::firstOrCreate(['name' => UserRole::Publication->value]);
        $publication->syncPermissions($permissions);

        $superAdmin = Role::where('name', UserRole::SuperAdmin->value)->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }
}
