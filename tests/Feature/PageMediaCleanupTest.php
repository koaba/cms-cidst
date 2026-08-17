<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Role::create(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('supprime le média et le fichier physique quand on supprime la page', function () {
    $this->actingAs($this->admin);

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->post(route('admin.pages.store'), [
        'title' => 'Page test',
        'content' => 'Contenu',
        'published_at' => '2026-08-14',
        'image' => $file,
        'is_published' => true,
    ]);

    $page = Page::where('title', 'Page test')->firstOrFail();
    $media = $page->media()->firstOrFail();
    $path = $media->path;

    Storage::disk('public')->assertExists($path);

    $this->delete(route('admin.pages.destroy', $page));

    expect(Media::find($media->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('ne supprime pas le média encore utilisé par un autre contenu', function () {
    $this->actingAs($this->admin);

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->post(route('admin.pages.store'), [
        'title' => 'Page A',
        'content' => 'Contenu A',
        'published_at' => '2026-08-14',
        'image' => $file,
        'is_published' => true,
    ]);

    $pageA = Page::where('title', 'Page A')->firstOrFail();
    $sharedMedia = $pageA->media()->firstOrFail();

    // On simule un deuxième contenu partageant le même média
    $pageB = Page::factory()->create(['title' => 'Page B']);
    $pageB->media()->attach($sharedMedia->id, ['order' => 0]);

    $this->delete(route('admin.pages.destroy', $pageA));

    // Le média doit survivre car encore utilisé par Page B
    expect(Media::find($sharedMedia->id))->not->toBeNull();
});

it('supprime l\'ancien média orphelin quand on remplace l\'image de la page', function () {
    $this->actingAs($this->admin);

    $firstFile = UploadedFile::fake()->image('ancienne.jpg');

    $this->post(route('admin.pages.store'), [
        'title' => 'Page à modifier',
        'content' => 'Contenu',
        'published_at' => '2026-08-14',
        'image' => $firstFile,
        'is_published' => true,
    ]);

    $page = Page::where('title', 'Page à modifier')->firstOrFail();
    $oldMedia = $page->media()->firstOrFail();
    $oldPath = $oldMedia->path;

    $newFile = UploadedFile::fake()->image('nouvelle.jpg');

    $this->put(route('admin.pages.update', $page), [
        'title' => 'Page à modifier',
        'content' => 'Contenu',
        'published_at' => '2026-08-14',
        'image' => $newFile,
        'is_published' => true,
    ]);

    expect(Media::find($oldMedia->id))->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);

    $page->refresh();
    expect($page->media()->count())->toBe(1);
});