<?php

use App\Models\Page;
use App\Models\User;
use App\Services\WatermarkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    Storage::fake('public');
});

it('cree un bloc image via upload', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'image', 'image' => UploadedFile::fake()->image('photo.jpg'), 'alt' => 'Texte alternatif']
    );

    $response->assertRedirect(route('admin.pages.blocks.index', $page));
    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->type)->toBe('image');
    expect($block->data['alt'])->toBe('Texte alternatif');
    expect($block->media()->count())->toBe(1);
});

it('rejette un bloc image sans fichier a la creation', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'image', 'alt' => 'Sans fichier']
    );

    $response->assertSessionHasErrors('image');
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(0);
});

it('applique le filigrane a une image de bloc quand la case est cochee', function () {
    $page = Page::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkImage')->once()->andReturn(true);
    });

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'image', 'image' => UploadedFile::fake()->image('photo.jpg'), 'apply_watermark' => '1']
    );
});

it('n\'applique pas le filigrane sur une image de bloc quand la case n\'est pas cochee', function () {
    $page = Page::factory()->create();

    $this->mock(WatermarkService::class, function ($mock) {
        $mock->shouldReceive('watermarkImage')->never();
    });

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'image', 'image' => UploadedFile::fake()->image('photo.jpg')]
    );
});

it('remplace l\'image existante d\'un bloc lors d\'une modification', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'image', 'image' => UploadedFile::fake()->image('ancienne.jpg')]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();
    $oldMediaId = $block->media()->first()->id;

    $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['image' => UploadedFile::fake()->image('nouvelle.jpg')]
    );

    $block->refresh();
    expect($block->media()->count())->toBe(1);
    expect($block->media()->first()->id)->not->toBe($oldMediaId);
});

it('supprime l\'image d\'un bloc quand delete_image est envoye', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'image', 'image' => UploadedFile::fake()->image('photo.jpg')]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->media()->count())->toBe(1);

    $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['delete_image' => '1']
    );

    expect($block->fresh()->media()->count())->toBe(0);
});
