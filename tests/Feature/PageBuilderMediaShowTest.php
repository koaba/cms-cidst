<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Queue::fake();
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
});

function builderShowBlocks($test, Page $page): array
{
    $builder = null;

    $test->actingAs($test->admin)
        ->get(route('admin.pages.builder.show', $page))
        ->assertOk()
        ->assertViewHas('builder', function (array $data) use (&$builder) {
            $builder = $data;

            return true;
        });

    return $builder['blocks'];
}

it("expose le media d'un bloc image", function () {
    $media = Media::factory()->create(['original_name' => 'logo.jpg']);
    $block = $this->page->blocks()->create(['type' => 'image', 'data' => ['alt' => 'Logo'], 'order' => 0]);
    $block->media()->attach($media->id, ['order' => 0]);

    $shown = builderShowBlocks($this, $this->page)[0];

    expect($shown['locked'])->toBeFalse();
    expect($shown['media'])->toEqual([
        'id' => $media->id,
        'url' => $media->thumbnail_url,
        'name' => 'logo.jpg',
    ]);
});

it('expose media null pour une image sans media', function () {
    $this->page->blocks()->create(['type' => 'image', 'data' => ['alt' => ''], 'order' => 0]);

    $shown = builderShowBlocks($this, $this->page)[0];

    expect(array_key_exists('media', $shown))->toBeTrue();
    expect($shown['media'])->toBeNull();
});

it('expose aussi le media de la banniere_hero', function () {
    $media = Media::factory()->create();
    $block = $this->page->blocks()->create(['type' => 'banniere_hero', 'data' => ['titre' => 'Hero'], 'order' => 0]);
    $block->media()->attach($media->id, ['order' => 0]);

    expect(builderShowBlocks($this, $this->page)[0]['media']['id'])->toBe($media->id);
});

it("n'expose pas de media pour un texte ni pour un bloc verrouille", function () {
    $media = Media::factory()->create();
    $this->page->blocks()->create(['type' => 'texte', 'data' => ['content' => 'A'], 'order' => 0]);
    $galerie = $this->page->blocks()->create(['type' => 'galerie', 'data' => [], 'order' => 1]);
    $galerie->media()->attach($media->id, ['order' => 0]);

    $shown = builderShowBlocks($this, $this->page);

    expect(array_key_exists('media', $shown[0]))->toBeFalse();
    expect($shown[1]['locked'])->toBeTrue();
    expect(array_key_exists('media', $shown[1]))->toBeFalse();
});

it('inclut le selecteur de mediatheque dans la page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.pages.builder.show', $this->page))
        ->assertOk()
        ->assertSee('media-picker-modal', false);
});
