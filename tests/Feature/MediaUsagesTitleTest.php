<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\Menu;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('affiche le libellé d\'un menu dans les usages d\'un média (pas un titre vide)', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $menu = Menu::create(['label' => 'Menu Accueil', 'target' => '/accueil', 'order' => 0, 'is_active' => true]);
    $media = Media::create([
        'path' => 'test/icone-menu.png',
        'original_name' => 'icone-menu.png',
        'mime_type' => 'image/png',
        'size' => 10240,
    ]);
    $menu->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($admin)->get(route('admin.media.index'));

    $response->assertOk();
    $response->assertSee('Menu Accueil');
});

it('affiche le nom d\'une catégorie dans les usages d\'un média (pas un titre vide)', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $category = Category::create(['name' => 'Actualités']);
    $media = Media::create([
        'path' => 'test/icone-categorie.png',
        'original_name' => 'icone-categorie.png',
        'mime_type' => 'image/png',
        'size' => 10240,
    ]);
    $category->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($admin)->get(route('admin.media.index'));

    $response->assertOk();
    $response->assertSee('Actualités');
});