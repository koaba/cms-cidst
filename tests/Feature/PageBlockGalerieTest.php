<?php

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    Storage::fake('public');
});

it('caste autoplay_interval en entier lors de la creation d\'un bloc galerie en carrousel', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'galerie',
            'layout' => 'carousel',
            'images' => [UploadedFile::fake()->image('photo1.jpg')],
            'autoplay' => '1',
            'autoplay_interval' => '10',
        ]
    );

    $response->assertRedirect(route('admin.pages.blocks.index', $page));
    $response->assertSessionHas('success');

    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block)->not->toBeNull();
    expect($block->type)->toBe('galerie');
    expect($block->data['autoplay'])->toBeTrue();
    expect($block->data['autoplay_interval'])->toBe(10);
    expect($block->data['autoplay_interval'])->toBeInt();
});

it('applique la valeur par defaut de autoplay_interval quand elle n\'est pas fournie', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'galerie',
            'layout' => 'carousel',
            'images' => [UploadedFile::fake()->image('photo1.jpg')],
        ]
    );

    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->data['autoplay_interval'])->toBe(4);
});

it('rejette un autoplay_interval en dessous de la borne minimale', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'galerie',
            'layout' => 'carousel',
            'images' => [UploadedFile::fake()->image('photo1.jpg')],
            'autoplay' => '1',
            'autoplay_interval' => '1',
        ]
    );

    $response->assertSessionHasErrors('autoplay_interval');
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(0);
});

it('rejette un autoplay_interval au dessus de la borne maximale', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'galerie',
            'layout' => 'carousel',
            'images' => [UploadedFile::fake()->image('photo1.jpg')],
            'autoplay' => '1',
            'autoplay_interval' => '31',
        ]
    );

    $response->assertSessionHasErrors('autoplay_interval');
    expect($page->blocks()->whereNull('parent_id')->count())->toBe(0);
});

it('affiche sans erreur une page publique avec un bloc galerie carrousel autoplay et plusieurs images', function () {
    $page = Page::factory()->create([
        'is_published' => true,
    ]);

    $block = PageBlock::create([
        'page_id' => $page->id,
        'type' => 'galerie',
        'data' => [
            'layout' => 'carousel',
            'autoplay' => true,
            'autoplay_interval' => 5,
        ],
        'order' => 1,
    ]);

    foreach (['a.jpg', 'b.jpg'] as $i => $name) {
        $media = \App\Models\Media::create([
            'path' => 'pages/' . $name,
            'original_name' => $name,
            'mime_type' => 'image/jpeg',
            'size' => 1000,
            'type' => 'image',
        ]);
        $block->media()->attach($media->id, ['order' => $i]);
    }

    $response = $this->get(route('pages.show', $page));

    $response->assertOk();
    $response->assertSee('carousel-' . $block->id, false);
});
