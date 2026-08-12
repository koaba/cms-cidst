<?php

namespace Database\Seeders;

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

        $publication = Role::firstOrCreate(['name' => 'Publication']);
        $publication->syncPermissions($permissions);

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }
}