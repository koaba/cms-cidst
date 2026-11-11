<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\SeoMeta;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['media.webp.enabled' => true, 'media.webp.quality' => 82]);
});

/** Image bruitee : un JPEG qualite 100 pese bien plus lourd que son equivalent WebP. */
function cvtNoisy(int $width, int $height): GdImage
{
    mt_srand(11);
    $image = imagecreatetruecolor($width, $height);

    for ($i = 0; $i < 400; $i++) {
        $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagefilledrectangle($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
    }

    return $image;
}

function cvtBytes(callable $draw): string
{
    ob_start();
    $draw();

    return (string) ob_get_clean();
}

function cvtJpeg(int $width = 600, int $height = 400, int $quality = 100): string
{
    return cvtBytes(fn () => imagejpeg(cvtNoisy($width, $height), null, $quality));
}

function cvtPng(): string
{
    return cvtBytes(fn () => imagepng(cvtNoisy(300, 200), null, 0));
}

/** JPEG 300x200 portant une orientation EXIF (6 = rotation de 90 degres a la lecture). */
function cvtExifJpeg(int $orientation): string
{
    $jpeg = cvtJpeg(300, 200);
    $tiff = "MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00\x00\x00\x00\x00";
    $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;

    return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
}

function cvtPut(string $path, string $bytes): string
{
    Storage::disk('public')->put($path, $bytes);

    return $path;
}

function cvtMedia(string $path, array $attributes = []): Media
{
    return Media::factory()->create(array_merge([
        'path' => $path,
        'type' => 'image',
        'mime_type' => str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg',
        'size' => Storage::disk('public')->size($path),
    ], $attributes));
}

it('ImageOptimizer::convert ecrit le WebP et garde l original', function () {
    $original = cvtPut('pages/photo.jpg', cvtJpeg());

    $result = app(ImageOptimizer::class)->convert($original);

    expect($result)->toBe('pages/photo.webp');
    Storage::disk('public')->assertExists('pages/photo.webp');
    Storage::disk('public')->assertExists('pages/photo.jpg');
});

it('ImageOptimizer::convert ne remplace pas un fichier WebP deja present', function () {
    $original = cvtPut('pages/photo.jpg', cvtJpeg());
    $other = cvtPut('pages/photo.webp', 'contenu d un autre fichier');

    $result = app(ImageOptimizer::class)->convert($original);

    expect($result)->toBe($original)
        ->and(Storage::disk('public')->get($other))->toBe('contenu d un autre fichier');
});

it('par defaut la commande simule et ne change rien', function () {
    $path = cvtPut('pages/photo.jpg', cvtJpeg());
    $media = cvtMedia($path);

    $this->artisan('media:convert-webp')
        ->expectsOutputToContain('(simulation)')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('pages/photo.jpg');
    Storage::disk('public')->assertMissing('pages/photo.webp');
    expect($media->fresh()->path)->toBe($path)
        ->and($media->fresh()->mime_type)->toBe('image/jpeg');
});

it('--dry-run l emporte sur --force', function () {
    $path = cvtPut('pages/photo.jpg', cvtJpeg());
    $media = cvtMedia($path);

    $this->artisan('media:convert-webp --force --dry-run')->assertSuccessful();

    Storage::disk('public')->assertExists($path);
    expect($media->fresh()->path)->toBe($path);
});

it('--force convertit le fichier et met a jour chemin, type MIME et taille du media', function () {
    $path = cvtPut('pages/photo.jpg', cvtJpeg());
    $media = cvtMedia($path);

    $this->artisan('media:convert-webp --force')->assertSuccessful();

    $media->refresh();
    expect($media->path)->toBe('pages/photo.webp')
        ->and($media->mime_type)->toBe('image/webp')
        ->and($media->size)->toBe(Storage::disk('public')->size('pages/photo.webp'));
    Storage::disk('public')->assertExists('pages/photo.webp');
    Storage::disk('public')->assertMissing('pages/photo.jpg');
});

it('convertit aussi un PNG', function () {
    $media = cvtMedia(cvtPut('pages/logo.png', cvtPng()));

    $this->artisan('media:convert-webp --force')->assertSuccessful();

    expect($media->fresh()->path)->toBe('pages/logo.webp');
    Storage::disk('public')->assertMissing('pages/logo.png');
});

it('reecrit toutes les references au chemin : slider, article, image sociale et logo', function () {
    $path = cvtPut('shared/photo.jpg', cvtJpeg());
    $slider = Slider::factory()->create(['image' => $path]);
    $article = Article::factory()->create(['image' => $path]);
    $seo = new SeoMeta(['og_image' => $path]);
    $seo->seoable_type = Article::class;
    $seo->seoable_id = $article->id;
    $seo->save();
    SiteSetting::current()->update(['logo_path' => $path]);

    $this->artisan('media:convert-webp --force')->assertSuccessful();

    expect($slider->fresh()->image)->toBe('shared/photo.webp')
        ->and($article->fresh()->image)->toBe('shared/photo.webp')
        ->and($seo->fresh()->og_image)->toBe('shared/photo.webp')
        ->and(SiteSetting::query()->first()->logo_path)->toBe('shared/photo.webp');
    Storage::disk('public')->assertMissing($path);
});

it('convertit un fichier cite une seule fois meme s il est reference partout', function () {
    $path = cvtPut('shared/photo.jpg', cvtJpeg());
    cvtMedia($path);
    Slider::factory()->create(['image' => $path]);

    $this->artisan('media:convert-webp --force')
        ->expectsOutputToContain('1 image(s) convertie(s)')
        ->assertSuccessful();
});

it('regenere miniature et variantes a partir du WebP', function () {
    $path = cvtPut('pages/large.jpg', cvtJpeg(1400, 300));
    $media = cvtMedia($path, [
        'thumbnail_path' => cvtPut('pages/thumbnails/large.jpg', 'ancienne miniature'),
        'variants' => [480 => cvtPut('pages/variants/large-480.webp', 'ancienne variante')],
    ]);

    $this->artisan('media:convert-webp --force')->assertSuccessful();

    $media->refresh();
    expect($media->thumbnail_path)->toBe('pages/thumbnails/large.webp')
        ->and(array_keys($media->variants))->toBe([480, 768, 1280]);
    Storage::disk('public')->assertExists('pages/thumbnails/large.webp');
    Storage::disk('public')->assertMissing('pages/thumbnails/large.jpg');

    foreach ($media->variants as $variant) {
        expect(getimagesize(Storage::disk('public')->path($variant))['mime'])->toBe('image/webp');
    }
});

it('relit les dimensions apres l orientation EXIF', function () {
    if (! function_exists('exif_read_data')) {
        $this->markTestSkipped('ext-exif absente.');
    }

    $media = cvtMedia(cvtPut('pages/portrait.jpg', cvtExifJpeg(6)), ['width' => 300, 'height' => 200]);

    $this->artisan('media:convert-webp --force')->assertSuccessful();

    $media->refresh();
    expect([$media->width, $media->height])->toBe([200, 300]);
});

it('--limit ne traite que le nombre demande', function () {
    cvtMedia(cvtPut('pages/a.jpg', cvtJpeg()));
    cvtMedia(cvtPut('pages/b.jpg', cvtJpeg()));
    cvtMedia(cvtPut('pages/c.jpg', cvtJpeg()));

    $this->artisan('media:convert-webp --force --limit=2')->assertSuccessful();

    expect(Media::where('mime_type', 'image/webp')->count())->toBe(2)
        ->and(Media::where('mime_type', 'image/jpeg')->count())->toBe(1);
});

it('refuse une valeur de --limit invalide', function () {
    $path = cvtPut('pages/a.jpg', cvtJpeg());
    cvtMedia($path);

    $this->artisan('media:convert-webp --force --limit=0')->assertFailed();

    Storage::disk('public')->assertExists($path);
});

it('laisse tel quel un fichier dont le WebP n est pas plus leger', function () {
    config(['media.webp.quality' => 100]);
    $path = cvtPut('pages/lourd.jpg', cvtJpeg(600, 400, 5));
    $media = cvtMedia($path);

    $this->artisan('media:convert-webp --force')->assertSuccessful();

    Storage::disk('public')->assertExists($path);
    expect($media->fresh()->path)->toBe($path);
});

it('ignore les GIF, les WebP, les PDF et les fichiers absents sans planter', function () {
    $gif = cvtPut('pages/anim.gif', cvtBytes(fn () => imagegif(cvtNoisy(50, 50))));
    $webp = cvtPut('pages/deja.webp', cvtBytes(fn () => imagewebp(cvtNoisy(50, 50), null, 80)));
    $pdf = cvtPut('docs/notice.pdf', '%PDF-1.4 faux');
    cvtMedia($gif, ['mime_type' => 'image/gif']);
    cvtMedia($webp, ['mime_type' => 'image/webp']);
    cvtMedia($pdf, ['type' => 'document', 'mime_type' => 'application/pdf']);
    $missing = cvtMedia('pages/disparu.jpg', ['size' => 1234]);

    $this->artisan('media:convert-webp --force')
        ->expectsOutputToContain('0 image(s) convertie(s)')
        ->assertSuccessful();

    Storage::disk('public')->assertExists([$gif, $webp, $pdf]);
    expect($missing->fresh()->path)->toBe('pages/disparu.jpg');
});

it('ne touche pas a un WebP voisin du meme nom', function () {
    $path = cvtPut('pages/photo.jpg', cvtJpeg());
    cvtPut('pages/photo.webp', 'contenu d un autre fichier');
    $media = cvtMedia($path);

    $this->artisan('media:convert-webp --force')->assertSuccessful();

    expect(Storage::disk('public')->get('pages/photo.webp'))->toBe('contenu d un autre fichier')
        ->and($media->fresh()->path)->toBe($path);
    Storage::disk('public')->assertExists($path);
});

it('est idempotente : un second passage ne trouve plus rien', function () {
    cvtMedia(cvtPut('pages/photo.jpg', cvtJpeg()));

    $this->artisan('media:convert-webp --force')->assertSuccessful();
    $this->artisan('media:convert-webp --force')
        ->expectsOutputToContain('0 image(s) convertie(s)')
        ->assertSuccessful();
});

it('annonce le gain en octets apres une conversion', function () {
    cvtMedia(cvtPut('pages/photo.jpg', cvtJpeg()));

    $this->artisan('media:convert-webp --force')
        ->expectsOutputToContain('Gain')
        ->assertSuccessful();
});

it('vide le cache des pages publiques seulement quand un fichier a ete converti', function () {
    Cache::forever('public.pages.version', 'avant');

    $this->artisan('media:convert-webp --force')->assertSuccessful();
    expect(Cache::get('public.pages.version'))->toBe('avant');

    cvtMedia(cvtPut('pages/photo.jpg', cvtJpeg()));

    $this->artisan('media:convert-webp --force')->assertSuccessful();
    expect(Cache::get('public.pages.version'))->not->toBe('avant');
});
