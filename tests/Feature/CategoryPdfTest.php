<?php

use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function actingAsAdminCategory(): User
{
    if (! Role::where('name', 'Super Admin')->exists()) {
        Role::create(['name' => 'Super Admin']);
    }
    $user = User::factory()->create();
    $user->assignRole('Super Admin');
    return $user;
}

it('cree une categorie avec upload direct de PDF', function () {
    Storage::fake('public');
    $user = actingAsAdminCategory();
    $file = UploadedFile::fake()->create('brochure.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)->post('/admin/categories', [
        'name' => 'Categorie test',
        'pdfs' => [$file],
    ]);

    $response->assertRedirect(route('admin.categories.index'));

    $category = Category::where('name', 'Categorie test')->first();
    expect($category)->not->toBeNull();
    expect($category->pdfs)->toHaveCount(1);

    $media = $category->pdfs->first();
    Storage::disk('public')->assertExists($media->path);
    expect($media->mime_type)->toBe('application/pdf');
});

it('cree une categorie via selection de PDF depuis la mediatheque', function () {
    Storage::fake('public');
    $user = actingAsAdminCategory();

    $file = UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf');
    $path = $file->store('categories/pdfs', 'public');
    $media = Media::create([
        'path' => $path,
        'original_name' => 'rapport.pdf',
        'mime_type' => 'application/pdf',
        'size' => 300,
    ]);

    $response = $this->actingAs($user)->post('/admin/categories', [
        'name' => 'Categorie mediatheque',
        'existing_media' => [$media->id],
    ]);

    $response->assertRedirect(route('admin.categories.index'));

    $category = Category::where('name', 'Categorie mediatheque')->first();
    expect($category->pdfs()->where('media.id', $media->id)->exists())->toBeTrue();
});

it('refuse plus de 10 documents PDF sur une categorie', function () {
    Storage::fake('public');
    $user = actingAsAdminCategory();

    $files = collect(range(1, 11))->map(
        fn ($i) => UploadedFile::fake()->create("doc{$i}.pdf", 100, 'application/pdf')
    )->all();

    $response = $this->actingAs($user)->post('/admin/categories', [
        'name' => 'Categorie trop de pdf',
        'pdfs' => $files,
    ]);

    $response->assertSessionHasErrors('pdfs');
    $this->assertDatabaseMissing('categories', ['name' => 'Categorie trop de pdf']);
});

it('rejette un ajout de PDF qui depasse le quota configure', function () {
    config(['media.max_pdfs' => 2]);
    Storage::fake('public');
    $user = actingAsAdminCategory();

    $category = Category::create(['name' => 'Categorie quota']);
    Media::factory()->count(2)->create(['mime_type' => 'application/pdf'])
        ->each(fn ($media) => $category->media()->attach($media->id, ['order' => 0]));

    $response = $this->actingAs($user)->put(route('admin.categories.update', $category), [
        'name' => 'Categorie quota',
        'pdfs' => [UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf')],
    ]);

    $response->assertSessionHasErrors('pdfs');
    expect($category->fresh()->pdfs)->toHaveCount(2);
});

it('empeche de supprimer le PDF d\'une autre categorie via delete_pdfs', function () {
    Storage::fake('public');
    $user = actingAsAdminCategory();

    $categoryA = Category::create(['name' => 'Categorie A']);
    $fileA = UploadedFile::fake()->create('a.pdf', 200, 'application/pdf');
    $categoryA->attachUploadedFiles([$fileA], 'categories/pdfs');
    $mediaA = $categoryA->pdfs->first();

    $categoryB = Category::create(['name' => 'Categorie B']);

    $response = $this->actingAs($user)->put(route('admin.categories.update', $categoryB), [
        'name' => 'Categorie B',
        'delete_pdfs' => [$mediaA->id],
    ]);

    $response->assertSessionHasErrors('delete_pdfs.0');

    expect($categoryA->pdfs()->where('media.id', $mediaA->id)->exists())->toBeTrue();
});

it('met a jour une categorie en supprimant un PDF existant et en ajoutant un nouveau', function () {
    Storage::fake('public');
    $user = actingAsAdminCategory();

    $category = Category::create(['name' => 'Categorie edit']);
    $oldFile = UploadedFile::fake()->create('old.pdf', 150, 'application/pdf');
    $category->attachUploadedFiles([$oldFile], 'categories/pdfs');
    $oldMedia = $category->pdfs->first();

    $newFile = UploadedFile::fake()->create('new.pdf', 150, 'application/pdf');

    $response = $this->actingAs($user)->put(route('admin.categories.update', $category), [
        'name' => 'Categorie edit',
        'delete_pdfs' => [$oldMedia->id],
        'pdfs' => [$newFile],
    ]);

    $response->assertRedirect(route('admin.categories.index'));

    $category->refresh();
    expect($category->pdfs()->where('media.id', $oldMedia->id)->exists())->toBeFalse();
    expect($category->pdfs)->toHaveCount(1);
    expect($category->pdfs->first()->original_name)->toBe('new.pdf');
});

it('refuse la creation d\'une categorie sans nom', function () {
    Storage::fake('public');
    $user = actingAsAdminCategory();

    $response = $this->actingAs($user)->post('/admin/categories', []);

    $response->assertSessionHasErrors(['name']);
    $this->assertDatabaseCount('categories', 0);
});