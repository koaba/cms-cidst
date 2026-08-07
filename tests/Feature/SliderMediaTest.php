<?php

use App\Models\Media;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

it('cree un slider via upload direct de fichier', function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $file = UploadedFile::fake()->image('slide.jpg');

    $response = $this->actingAs($user)->post('/admin/sliders', [
        'title' => 'Slider test',
        'subtitle' => 'Sous-titre',
        'image' => $file,
        'link_url' => 'https://example.com',
        'order' => 0,
        'is_active' => true,
    ]);

    $response->assertRedirect('/admin/sliders');

    $slider = Slider::where('title', 'Slider test')->first();
    expect($slider)->not->toBeNull();
    expect($slider->image)->not->toBeNull();

    Storage::disk('public')->assertExists($slider->image);

    $media = Media::where('path', $slider->image)->first();
    expect($media)->not->toBeNull();
    expect($slider->media()->where('media.id', $media->id)->exists())->toBeTrue();
});

it('cree un slider via selection depuis la mediatheque', function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $file = UploadedFile::fake()->image('existing.jpg');
    $path = $file->store('sliders', 'public');
    $media = Media::create([
        'path' => $path,
        'original_name' => 'existing.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);

    $response = $this->actingAs($user)->post('/admin/sliders', [
        'title' => 'Slider depuis mediatheque',
        'existing_media_id' => $media->id,
        'order' => 0,
        'is_active' => true,
    ]);

    $response->assertRedirect('/admin/sliders');

    $slider = Slider::where('title', 'Slider depuis mediatheque')->first();
    expect($slider)->not->toBeNull();
    expect($slider->image)->toBe($path);
});

it('refuse la creation d\'un slider sans image', function () {
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $response = $this->actingAs($user)->post('/admin/sliders', [
        'title' => 'Slider sans image',
        'order' => 0,
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('image');
    $this->assertDatabaseMissing('sliders', ['title' => 'Slider sans image']);
});