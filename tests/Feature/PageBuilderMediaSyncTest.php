<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Queue::fake();
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
});

function builderMediaSave($test, Page $page, array $blocks)
{
    return $test->actingAs($test->admin)
        ->postJson(route('admin.pages.builder.save', $page), ['blocks' => $blocks]);
}

it('cree une image rattachee a un media existant', function () {
    $media = Media::factory()->create();

    builderMediaSave($this, $this->page, [
        ['type' => 'image', 'media_id' => $media->id, 'data' => ['alt' => 'Logo', 'caption' => 'Legende']],
    ])->assertOk()->assertJsonPath('blocks.0.type', 'image');

    $block = $this->page->blocks()->first();
    expect($block->media()->pluck('media.id')->all())->toEqual([$media->id]);
    expect($block->data)->toEqual(['alt' => 'Logo', 'caption' => 'Legende']);
    expect(Media::count())->toBe(1);
});

it('refuse une image sans media_id a la creation', function () {
    builderMediaSave($this, $this->page, [
        ['type' => 'image', 'data' => ['alt' => 'Sans media']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.media_id');

    expect($this->page->blocks()->count())->toBe(0);
});

it('refuse un media_id inexistant', function () {
    builderMediaSave($this, $this->page, [
        ['type' => 'image', 'media_id' => 999999, 'data' => []],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.media_id');

    expect($this->page->blocks()->count())->toBe(0);
});

it("refuse un media qui n'est pas une image", function () {
    $pdf = Media::factory()->create(['mime_type' => 'application/pdf']);

    builderMediaSave($this, $this->page, [
        ['type' => 'image', 'media_id' => $pdf->id, 'data' => []],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.0.media_id');

    expect($this->page->blocks()->count())->toBe(0);
});

it('conserve le media actuel quand media_id est absent', function () {
    $media = Media::factory()->create();
    $block = $this->page->blocks()->create(['type' => 'image', 'data' => ['alt' => 'Ancien'], 'order' => 0]);
    $block->media()->attach($media->id, ['order' => 0]);

    builderMediaSave($this, $this->page, [
        ['id' => $block->id, 'type' => 'image', 'data' => ['alt' => 'Nouveau']],
    ])->assertOk();

    expect($block->fresh()->data['alt'])->toBe('Nouveau');
    expect($block->media()->pluck('media.id')->all())->toEqual([$media->id]);
});

it('remplace le media quand media_id change', function () {
    $old = Media::factory()->create();
    $new = Media::factory()->create();
    $block = $this->page->blocks()->create(['type' => 'image', 'data' => ['alt' => 'X'], 'order' => 0]);
    $block->media()->attach($old->id, ['order' => 0]);

    builderMediaSave($this, $this->page, [
        ['id' => $block->id, 'type' => 'image', 'media_id' => $new->id, 'data' => ['alt' => 'X']],
    ])->assertOk();

    expect($block->media()->pluck('media.id')->all())->toEqual([$new->id]);
    $this->assertDatabaseMissing('media', ['id' => $old->id]);
});

it('cree une banniere_hero avec un media existant', function () {
    $media = Media::factory()->create();

    builderMediaSave($this, $this->page, [
        ['type' => 'banniere_hero', 'media_id' => $media->id, 'data' => ['titre' => 'Bienvenue', 'alt' => 'Vue']],
    ])->assertOk();

    $block = $this->page->blocks()->first();
    expect($block->media()->pluck('media.id')->all())->toEqual([$media->id]);
    expect($block->data['titre'])->toBe('Bienvenue');
    expect($block->data['overlay_opacity'])->toBe(40);
});

it("n'ecrit rien si un media_id est invalide", function () {
    $media = Media::factory()->create();

    builderMediaSave($this, $this->page, [
        ['type' => 'image', 'media_id' => $media->id, 'data' => ['alt' => 'A']],
        ['type' => 'image', 'media_id' => 999999, 'data' => ['alt' => 'B']],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks.1.media_id');

    expect($this->page->blocks()->count())->toBe(0);
    expect(DB::table('mediables')->count())->toBe(0);
});
