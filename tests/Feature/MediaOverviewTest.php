<?php

use App\Jobs\GenerateMediaThumbnail;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\MediaOverviewService;
use Illuminate\Support\Facades\Bus;

beforeEach(fn () => Bus::fake([GenerateMediaThumbnail::class]));

function overviewMedia(array $attributes = []): Media
{
    return Media::create($attributes + [
        'path' => 'pages/'.uniqid().'.jpg',
        'original_name' => 'fichier.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);
}

function overviewBlock(string $type, array $data = []): PageBlock
{
    return Page::factory()->create()->blocks()->create([
        'type' => $type,
        'data' => $data,
        'order' => 0,
    ]);
}

it('compte les médias par type et additionne le poids', function () {
    overviewMedia(['size' => 1000]);
    overviewMedia(['size' => 2000]);
    overviewMedia(['type' => 'video', 'mime_type' => 'video/mp4', 'size' => 5000]);
    overviewMedia(['type' => 'document', 'mime_type' => 'application/pdf', 'size' => 3000]);

    $overview = app(MediaOverviewService::class)->overview();

    expect($overview['counts'])->toBe(['image' => 2, 'pdf' => 1, 'video' => 1, 'other' => 0])
        ->and($overview['total_bytes'])->toBe(11000);
});

it('compte comme PDF un fichier PDF enregistré avec le type par défaut image', function () {
    overviewMedia(['type' => 'image', 'mime_type' => 'application/pdf']);

    $counts = app(MediaOverviewService::class)->overview()['counts'];

    expect($counts['pdf'])->toBe(1)
        ->and($counts['image'])->toBe(0);
});

it('formate les poids en unités lisibles', function () {
    expect(MediaOverviewService::formatBytes(0))->toBe('0 o')
        ->and(MediaOverviewService::formatBytes(1024))->toBe('1 Ko')
        ->and(MediaOverviewService::formatBytes(1572864))->toBe('1,5 Mo')
        ->and(MediaOverviewService::formatBytes(1073741824))->toBe('1 Go');
});

it('signale les médias rattachés à aucun contenu', function () {
    $used = overviewMedia();
    overviewMedia();
    overviewMedia();

    overviewBlock('image')->media()->attach($used->id, ['order' => 0]);

    expect(app(MediaOverviewService::class)->overview()['alerts']['orphans'])->toBe(2);
});

it('signale uniquement les images strictement plus lourdes que le seuil', function () {
    $limit = MediaOverviewService::HEAVY_IMAGE_BYTES;

    overviewMedia(['size' => $limit]);
    overviewMedia(['size' => $limit + 1]);
    overviewMedia(['type' => 'video', 'mime_type' => 'video/mp4', 'size' => $limit * 10]);
    overviewMedia(['type' => 'document', 'mime_type' => 'application/pdf', 'size' => $limit * 10]);

    expect(app(MediaOverviewService::class)->overview()['alerts']['heavy_images'])->toBe(1);
});

it('signale les images sans texte alternatif, bloc image et galerie confondus', function () {
    $sansAlt = overviewMedia();
    $avecAlt = overviewMedia();
    $heroSansMedia = overviewBlock('banniere_hero', ['alt' => null]);
    $galerieA = overviewMedia();
    $galerieB = overviewMedia();

    overviewBlock('image', ['alt' => null])->media()->attach($sansAlt->id, ['order' => 0]);
    overviewBlock('image', ['alt' => 'Logo'])->media()->attach($avecAlt->id, ['order' => 0]);

    $galerie = overviewBlock('galerie');
    $galerie->media()->attach($galerieA->id, ['order' => 0, 'alt' => null]);
    $galerie->media()->attach($galerieB->id, ['order' => 1, 'alt' => 'Texte']);

    expect(app(MediaOverviewService::class)->overview()['alerts']['missing_alt'])->toBe(2);
});

it('liste les six derniers médias, le plus récent en premier', function () {
    foreach (range(1, 7) as $i) {
        $media = overviewMedia(['original_name' => "media-$i.jpg"]);
        Media::query()->whereKey($media->id)->update(['created_at' => now()->subMinutes(10 - $i)]);

        if ($i === 7) {
            overviewBlock('image')->media()->attach($media->id, ['order' => 0]);
        }
    }

    $recent = app(MediaOverviewService::class)->overview()['recent'];

    expect($recent)->toHaveCount(6)
        ->and($recent[0]['name'])->toBe('media-7.jpg')
        ->and($recent[0]['used'])->toBe(1)
        ->and($recent[1]['used'])->toBe(0)
        ->and($recent[5]['name'])->toBe('media-2.jpg');
});

it('affiche la section médiathèque', function () {
    overviewMedia(['original_name' => 'logo-cidst.png']);

    $overview = app(MediaOverviewService::class)->overview();

    $this->blade('<x-admin.media-overview :overview="$overview" />', ['overview' => $overview])
        ->assertSee('Médiathèque')
        ->assertSee('Images')
        ->assertSee('logo-cidst.png');
});
