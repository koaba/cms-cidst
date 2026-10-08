<?php

use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('dispatche le job uniquement pour les images sans variantes', function () {
    Media::factory()->create(['type' => 'image', 'variants' => null]);
    Media::factory()->create(['type' => 'image', 'variants' => [480 => 'a/variants/x-480.webp']]);
    Media::factory()->create(['type' => 'document', 'variants' => null]);

    Queue::fake();

    $this->artisan('media:backfill-variants')->assertSuccessful();

    Queue::assertPushed(GenerateMediaVariants::class, 1);
});

it('ne dispatche rien en simulation', function () {
    Media::factory()->create(['type' => 'image', 'variants' => null]);

    Queue::fake();

    $this->artisan('media:backfill-variants --dry-run')
        ->expectsOutputToContain('(simulation)')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});
