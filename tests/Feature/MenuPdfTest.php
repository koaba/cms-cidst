<?php

use App\Models\Media;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function actingAsAdmin(): User
{
    if (! Role::where('name', 'Super Admin')->exists()) {
        Role::create(['name' => 'Super Admin']);
    }
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    return $user;
}

it('cree un menu avec upload direct de PDF', function () {
    Storage::fake('public');
    $user = actingAsAdmin();
    $file = UploadedFile::fake()->create('brochure.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)->post('/admin/menus', [
        'label' => 'Menu test',
        'target' => '/contact',
        'order' => 0,
        'is_active' => true,
        'pdfs' => [$file],
    ]);

    $response->assertRedirect(route('admin.menus.index'));

    $menu = Menu::where('label', 'Menu test')->first();
    expect($menu)->not->toBeNull();
    expect($menu->pdfs)->toHaveCount(1);

    $media = $menu->pdfs->first();
    Storage::disk('public')->assertExists($media->path);
    expect($media->mime_type)->toBe('application/pdf');
});

it('cree un menu via selection de PDF depuis la mediatheque', function () {
    Storage::fake('public');
    $user = actingAsAdmin();

    $file = UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf');
    $path = $file->store('menus/pdfs', 'public');
    $media = Media::create([
        'path' => $path,
        'original_name' => 'rapport.pdf',
        'mime_type' => 'application/pdf',
        'size' => 300,
    ]);

    $response = $this->actingAs($user)->post('/admin/menus', [
        'label' => 'Menu mediatheque',
        'target' => '/rapports',
        'order' => 0,
        'is_active' => true,
        'existing_media' => [$media->id],
    ]);

    $response->assertRedirect(route('admin.menus.index'));

    $menu = Menu::where('label', 'Menu mediatheque')->first();
    expect($menu->pdfs()->where('media.id', $media->id)->exists())->toBeTrue();
});

it('refuse plus de 10 documents PDF sur un menu', function () {
    Storage::fake('public');
    $user = actingAsAdmin();

    $files = collect(range(1, 11))->map(
        fn ($i) => UploadedFile::fake()->create("doc{$i}.pdf", 100, 'application/pdf')
    )->all();

    $response = $this->actingAs($user)->post('/admin/menus', [
        'label' => 'Menu trop de pdf',
        'target' => '/trop',
        'order' => 0,
        'is_active' => true,
        'pdfs' => $files,
    ]);

    $response->assertSessionHasErrors('pdfs');
    $this->assertDatabaseMissing('menus', ['label' => 'Menu trop de pdf']);
});

it('empeche de supprimer le PDF d\'un autre menu via delete_pdfs', function () {
    Storage::fake('public');
    $user = actingAsAdmin();

    // Menu A avec son PDF
    $menuA = Menu::create(['label' => 'Menu A', 'target' => '/a', 'order' => 0, 'is_active' => true]);
    $fileA = UploadedFile::fake()->create('a.pdf', 200, 'application/pdf');
    $menuA->attachUploadedFiles([$fileA], 'menus/pdfs');
    $mediaA = $menuA->pdfs->first();

    // Menu B, cible de l'attaque : tenter de detacher le PDF de A via B
    $menuB = Menu::create(['label' => 'Menu B', 'target' => '/b', 'order' => 1, 'is_active' => true]);

    $response = $this->actingAs($user)->put(route('admin.menus.update', $menuB), [
        'label' => 'Menu B',
        'target' => '/b',
        'order' => 1,
        'is_active' => true,
        'delete_pdfs' => [$mediaA->id],
    ]);

    $response->assertSessionHasErrors('delete_pdfs.0');

    // Le media de A doit toujours etre attache a A
    expect($menuA->pdfs()->where('media.id', $mediaA->id)->exists())->toBeTrue();
});

it('met a jour un menu en supprimant un PDF existant et en ajoutant un nouveau', function () {
    Storage::fake('public');
    $user = actingAsAdmin();

    $menu = Menu::create(['label' => 'Menu edit', 'target' => '/edit', 'order' => 0, 'is_active' => true]);
    $oldFile = UploadedFile::fake()->create('old.pdf', 150, 'application/pdf');
    $menu->attachUploadedFiles([$oldFile], 'menus/pdfs');
    $oldMedia = $menu->pdfs->first();

    $newFile = UploadedFile::fake()->create('new.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)->put(route('admin.menus.update', $menu), [
        'label' => 'Menu edit',
        'target' => '/edit',
        'order' => 0,
        'is_active' => true,
        'delete_pdfs' => [$oldMedia->id],
        'pdfs' => [$newFile],
    ]);

    $response->assertRedirect(route('admin.menus.index'));

    $menu->refresh();
    expect($menu->pdfs()->where('media.id', $oldMedia->id)->exists())->toBeFalse();
    expect($menu->pdfs)->toHaveCount(1);
    expect($menu->pdfs->first()->original_name)->toBe('new.pdf');
});

it('refuse la creation d\'un menu sans libelle ou sans cible', function () {
    $user = actingAsAdmin();

    $response = $this->actingAs($user)->post('/admin/menus', [
        'order' => 0,
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors(['label', 'target']);
    $this->assertDatabaseCount('menus', 0);
});