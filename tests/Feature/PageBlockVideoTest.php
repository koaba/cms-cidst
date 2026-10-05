<?php

use App\Models\Page;
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

it('cree un bloc video via upload', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        [
            'type' => 'video',
            'source_type' => 'upload',
            'video_file' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
        ]
    );

    $response->assertRedirect(route('admin.pages.blocks.index', $page));
    $block = $page->blocks()->whereNull('parent_id')->first();

    expect($block->type)->toBe('video');
    expect($block->data['source_type'])->toBe('upload');
    expect($block->media()->count())->toBe(1);
});

it('cree un bloc video via url externe', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'url', 'url' => 'https://youtube.com/watch?v=abc']
    );

    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->data['source_type'])->toBe('url');
    expect($block->data['url'])->toBe('https://youtube.com/watch?v=abc');
    expect($block->media()->count())->toBe(0);
});

it('rejette une video upload sans fichier', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'upload']
    );

    $response->assertSessionHasErrors('video_file');
});

it('rejette une video externe sans url', function () {
    $page = Page::factory()->create();

    $response = $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'url']
    );

    $response->assertSessionHasErrors('url');
});

it('supprime la video d\'un bloc quand delete_video et un nouveau fichier sont envoyes', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'upload', 'video_file' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4')]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->media()->count())->toBe(1);

    $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        [
            'source_type' => 'upload',
            'delete_video' => '1',
            'video_file' => UploadedFile::fake()->create('remplacement.mp4', 500, 'video/mp4'),
        ]
    );

    expect($block->fresh()->media()->count())->toBe(1);
});

it('FIX APPLIQUE: supprime une video uploadee sans renvoyer de fichier', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'upload', 'video_file' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4')]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['source_type' => 'upload', 'delete_video' => '1']
    );

    $response->assertSessionHasNoErrors();
    expect($block->fresh()->media()->count())->toBe(0);
});

it('detache la video uploadee quand on passe a une source url', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'upload', 'video_file' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4')]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->media()->count())->toBe(1);

    $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['source_type' => 'url', 'url' => 'https://youtube.com/watch?v=abc']
    );

    $block->refresh();
    expect($block->data['source_type'])->toBe('url');
    expect($block->media()->count())->toBe(0);
});
it('rejette le passage a upload sans fichier ni media existant', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'url', 'url' => 'https://youtube.com/watch?v=abc']
    );
    $block = $page->blocks()->whereNull('parent_id')->first();
    expect($block->media()->count())->toBe(0);

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['source_type' => 'upload']
    );

    $response->assertSessionHasErrors('video_file');
    expect($block->fresh()->data['source_type'])->toBe('url');
});

it('conserve la video existante quand on met a jour sans nouveau fichier', function () {
    $page = Page::factory()->create();
    $this->actingAs($this->admin)->post(
        route('admin.pages.blocks.store', $page),
        ['type' => 'video', 'source_type' => 'upload', 'video_file' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4')]
    );
    $block = $page->blocks()->whereNull('parent_id')->first();

    $response = $this->actingAs($this->admin)->put(
        route('admin.pages.blocks.update', [$page, $block->id]),
        ['source_type' => 'upload', 'title' => 'Nouveau titre']
    );

    $response->assertSessionHasNoErrors();
    expect($block->fresh()->media()->count())->toBe(1);
});
