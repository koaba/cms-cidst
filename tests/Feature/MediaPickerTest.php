<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Queue::fake();
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('expose le mime de chaque media du selecteur sans retirer les cles existantes', function () {
    $image = Media::factory()->create(['mime_type' => 'image/jpeg', 'original_name' => 'photo.jpg']);
    $pdf = Media::factory()->create(['mime_type' => 'application/pdf', 'original_name' => 'doc.pdf']);

    $items = collect(
        $this->actingAs($this->admin)->getJson(route('admin.media.picker'))->assertOk()->json('data')
    )->keyBy('id');

    expect($items[$image->id])->toHaveKeys(['id', 'url', 'name', 'mime']);
    expect($items[$image->id]['name'])->toBe('photo.jpg');
    expect($items[$image->id]['mime'])->toBe('image/jpeg');
    expect($items[$pdf->id]['mime'])->toBe('application/pdf');
});

it('filtre le selecteur par nom', function () {
    Media::factory()->create(['original_name' => 'logo-cidst.jpg']);
    Media::factory()->create(['original_name' => 'autre.jpg']);

    $names = collect(
        $this->actingAs($this->admin)->getJson(route('admin.media.picker', ['q' => 'cidst']))->assertOk()->json('data')
    )->pluck('name')->all();

    expect($names)->toBe(['logo-cidst.jpg']);
});
