<?php

use App\Models\Media;
use App\Models\Page;
use App\Services\BlockMediaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Queue::fake();
    Storage::fake('public');

    $this->page = Page::factory()->create();
    $this->block = $this->page->blocks()->create(['type' => 'image', 'data' => ['alt' => ''], 'order' => 0]);
    $this->service = app(BlockMediaService::class);
});

it('rattache un media existant sans le dupliquer', function () {
    $media = Media::factory()->create();

    $this->service->attachExisting($this->block, $media->id);

    expect(Media::count())->toBe(1);
    expect($this->block->media()->pluck('media.id')->all())->toEqual([$media->id]);
});

it("remplace l'ancien rattachement", function () {
    $old = Media::factory()->create();
    $new = Media::factory()->create();
    $this->block->media()->attach($old->id, ['order' => 0]);

    $this->service->attachExisting($this->block, $new->id);

    expect($this->block->media()->pluck('media.id')->all())->toEqual([$new->id]);
});

it("supprime l'ancien media s'il n'est plus utilise nulle part", function () {
    $old = Media::factory()->create();
    $new = Media::factory()->create();
    $this->block->media()->attach($old->id, ['order' => 0]);

    $this->service->attachExisting($this->block, $new->id);

    $this->assertDatabaseMissing('media', ['id' => $old->id]);
});

it("garde l'ancien media s'il est encore utilise par un autre bloc", function () {
    $old = Media::factory()->create();
    $new = Media::factory()->create();
    $other = $this->page->blocks()->create(['type' => 'image', 'data' => [], 'order' => 1]);
    $this->block->media()->attach($old->id, ['order' => 0]);
    $other->media()->attach($old->id, ['order' => 0]);

    $this->service->attachExisting($this->block, $new->id);

    $this->assertDatabaseHas('media', ['id' => $old->id]);
    expect($other->media()->count())->toBe(1);
});

it('garde le media quand on rattache celui deja en place', function () {
    $media = Media::factory()->create();
    $this->block->media()->attach($media->id, ['order' => 0]);

    $this->service->attachExisting($this->block, $media->id);

    $this->assertDatabaseHas('media', ['id' => $media->id]);
    expect($this->block->media()->pluck('media.id')->all())->toEqual([$media->id]);
});

it('refuse un id inexistant sans toucher au rattachement actuel', function () {
    $old = Media::factory()->create();
    $this->block->media()->attach($old->id, ['order' => 0]);

    expect(fn () => $this->service->attachExisting($this->block, 999999))
        ->toThrow(ModelNotFoundException::class);

    expect($this->block->media()->pluck('media.id')->all())->toEqual([$old->id]);
    $this->assertDatabaseHas('media', ['id' => $old->id]);
});

it("refuse un media qui n'est pas une image", function () {
    $pdf = Media::factory()->create(['mime_type' => 'application/pdf']);

    expect(fn () => $this->service->attachExisting($this->block, $pdf->id))
        ->toThrow(InvalidArgumentException::class);

    expect($this->block->media()->count())->toBe(0);
});
