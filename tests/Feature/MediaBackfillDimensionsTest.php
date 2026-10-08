<?php

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function backfillMedia(string $path, string $type = 'image', array $extra = []): Media
{
    return Media::create([
        'path' => $path,
        'original_name' => $extra['original_name'] ?? basename($path),
        'mime_type' => $type === 'image' ? 'image/jpeg' : 'application/pdf',
        'size' => 1000,
        'type' => $type,
    ] + $extra);
}

function seedBackfill(): void
{
    backfillMedia(UploadedFile::fake()->image('a.jpg', 120, 80)->store('pages', 'public'), 'image', ['original_name' => 'a.jpg']);
    backfillMedia(UploadedFile::fake()->image('b.jpg', 60, 40)->store('pages', 'public'));
    backfillMedia(
        UploadedFile::fake()->image('c.jpg', 200, 100)->store('pages', 'public'),
        'image',
        ['original_name' => 'c.jpg', 'width' => 10, 'height' => 10]
    );
    backfillMedia('pages/absent.jpg');
    backfillMedia(UploadedFile::fake()->create('d.pdf', 10, 'application/pdf')->store('pages', 'public'), 'document');
}

it('renseigne les dimensions manquantes sans retoucher les existantes', function () {
    seedBackfill();

    $this->artisan('media:backfill-dimensions')
        ->expectsOutputToContain('2 traite(s)')
        ->expectsOutputToContain('1 introuvable(s)')
        ->assertSuccessful();

    expect(Media::whereNotNull('width')->count())->toBe(3)
        ->and(Media::whereNull('width')->count())->toBe(2)
        ->and(Media::where('original_name', 'a.jpg')->value('width'))->toBe(120)
        ->and(Media::where('original_name', 'c.jpg')->value('width'))->toBe(10);
});

it('n\'ecrit rien en mode dry-run', function () {
    seedBackfill();

    $this->artisan('media:backfill-dimensions', ['--dry-run' => true])
        ->expectsOutputToContain('2 traite(s)')
        ->assertSuccessful();

    expect(Media::whereNotNull('width')->count())->toBe(1)
        ->and(Media::whereNull('width')->count())->toBe(4);
});

it('est idempotente', function () {
    seedBackfill();

    $this->artisan('media:backfill-dimensions')->assertSuccessful();
    $this->artisan('media:backfill-dimensions')
        ->expectsOutputToContain('0 traite(s)')
        ->assertSuccessful();

    expect(Media::whereNotNull('width')->count())->toBe(3);
});
