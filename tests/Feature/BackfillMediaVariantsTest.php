<?php

use App\Jobs\ProcessMediaImage;
use App\Models\Media;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

/** Identifiant du media porte par le job (propriete privee, lue par fermeture liee). */
function bfvMediaId(ProcessMediaImage $job): int
{
    return (fn () => $this->mediaId)->call($job);
}

it('dispatche le job uniquement pour les images sans variantes', function () {
    $sansVariantes = Media::factory()->create(['type' => 'image', 'variants' => null]);
    Media::factory()->create(['type' => 'image', 'variants' => [480 => 'a/variants/x-480.webp']]);
    Media::factory()->create(['type' => 'document', 'variants' => null]);

    Queue::fake();

    $this->artisan('media:backfill-variants')->assertSuccessful();

    Queue::assertPushed(ProcessMediaImage::class, 1);
    Queue::assertPushed(ProcessMediaImage::class, fn (ProcessMediaImage $job) => bfvMediaId($job) === $sansVariantes->id);
});

it('ne dispatche rien en simulation', function () {
    Media::factory()->create(['type' => 'image', 'variants' => null]);

    Queue::fake();

    $this->artisan('media:backfill-variants --dry-run')
        ->expectsOutputToContain('(simulation)')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});
