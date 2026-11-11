<?php

use App\Models\Media;
use App\Services\ImageOptimizer;
use App\Services\MediaSyncService;
use App\Services\MediaUploader;
use App\Services\WatermarkService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['media.webp.enabled' => true, 'media.webp.quality' => 82]);
});

/** Image bruitee : un JPEG qualite 100 pese bien plus lourd que son equivalent WebP. */
function webpNoisyImage(int $width, int $height): GdImage
{
    mt_srand(7);
    $image = imagecreatetruecolor($width, $height);

    for ($i = 0; $i < 400; $i++) {
        $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        imagefilledrectangle($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
    }

    return $image;
}

function webpBytes(callable $draw): string
{
    ob_start();
    $draw();

    return (string) ob_get_clean();
}

function webpJpegBytes(int $quality = 100, int $width = 600, int $height = 400): string
{
    return webpBytes(fn () => imagejpeg(webpNoisyImage($width, $height), null, $quality));
}

function webpAlphaPngBytes(): string
{
    $image = imagecreatetruecolor(300, 200);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

    mt_srand(7);
    for ($i = 0; $i < 60; $i++) {
        $color = imagecolorallocatealpha($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 0);
        imagefilledellipse($image, mt_rand(0, 300), mt_rand(0, 200), mt_rand(20, 80), mt_rand(20, 80), $color);
    }

    return webpBytes(fn () => imagepng($image, null, 0));
}

/** JPEG 300x200 portant une orientation EXIF (6 = rotation de 90 degres a la lecture). */
function webpExifJpegBytes(int $orientation): string
{
    $jpeg = webpJpegBytes(100, 300, 200);
    $tiff = "MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00\x00\x00\x00\x00";
    $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;

    return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
}

function webpPut(string $path, string $bytes): string
{
    Storage::disk('public')->put($path, $bytes);

    return $path;
}

it('convertit un JPEG en WebP, supprime l original et retourne le nouveau chemin', function () {
    $original = webpPut('pages/photo.jpg', webpJpegBytes());
    $originalSize = Storage::disk('public')->size($original);

    $result = app(ImageOptimizer::class)->optimize($original);

    expect($result)->toBe('pages/photo.webp');
    Storage::disk('public')->assertExists('pages/photo.webp');
    Storage::disk('public')->assertMissing('pages/photo.jpg');
    expect(getimagesize(Storage::disk('public')->path($result))['mime'])->toBe('image/webp')
        ->and(Storage::disk('public')->size($result))->toBeLessThan($originalSize);
});

it('garde les dimensions de l image', function () {
    $original = webpPut('pages/photo.jpg', webpJpegBytes(100, 600, 400));

    $result = app(ImageOptimizer::class)->optimize($original);

    $size = getimagesize(Storage::disk('public')->path($result));
    expect([$size[0], $size[1]])->toBe([600, 400]);
});

it('conserve l original quand le WebP n est pas plus leger', function () {
    config(['media.webp.quality' => 100]);
    $original = webpPut('pages/lourd.jpg', webpJpegBytes(5));
    $before = Storage::disk('public')->get($original);

    $result = app(ImageOptimizer::class)->optimize($original);

    expect($result)->toBe('pages/lourd.jpg')
        ->and(Storage::disk('public')->get($original))->toBe($before);
    Storage::disk('public')->assertMissing('pages/lourd.webp');
});

it('convertit un PNG en WebP en gardant la transparence', function () {
    $original = webpPut('pages/logo.png', webpAlphaPngBytes());

    $result = app(ImageOptimizer::class)->optimize($original);

    expect($result)->toBe('pages/logo.webp');
    $webp = imagecreatefromwebp(Storage::disk('public')->path($result));
    expect((imagecolorat($webp, 2, 2) >> 24) & 127)->toBe(127);
});

it('ne touche ni aux GIF, ni aux WebP, ni aux fichiers qui ne sont pas des images', function () {
    $gif = webpPut('pages/anim.gif', webpBytes(fn () => imagegif(webpNoisyImage(50, 50))));
    $webp = webpPut('pages/deja.webp', webpBytes(fn () => imagewebp(webpNoisyImage(50, 50), null, 80)));
    $fake = webpPut('pages/faux.jpg', 'ceci n est pas une image');

    $optimizer = app(ImageOptimizer::class);

    expect($optimizer->optimize($gif))->toBe($gif)
        ->and($optimizer->optimize($webp))->toBe($webp)
        ->and($optimizer->optimize($fake))->toBe($fake)
        ->and(Storage::disk('public')->get($fake))->toBe('ceci n est pas une image');
});

it('ne fait rien quand la conversion est desactivee', function () {
    config(['media.webp.enabled' => false]);
    $original = webpPut('pages/photo.jpg', webpJpegBytes());

    expect(app(ImageOptimizer::class)->optimize($original))->toBe($original);
    Storage::disk('public')->assertExists($original);
});

it('retourne le chemin recu quand le fichier n existe pas', function () {
    expect(app(ImageOptimizer::class)->optimize('pages/absent.jpg'))->toBe('pages/absent.jpg');
});

it('garde l original et journalise quand la memoire disponible est insuffisante', function () {
    Log::spy();
    $original = webpPut('pages/photo.jpg', webpJpegBytes());

    $optimizer = new class extends ImageOptimizer
    {
        protected function availableMemory(): int
        {
            return 1000;
        }
    };

    expect($optimizer->optimize($original))->toBe($original);
    Storage::disk('public')->assertExists($original);
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context = []) => $message === 'media.webp_skipped' && ($context['reason'] ?? null) === 'not_enough_memory')
        ->once();
});

it('applique l orientation EXIF aux pixels, que le WebP ne porte pas', function () {
    if (! function_exists('exif_read_data')) {
        $this->markTestSkipped('ext-exif absente.');
    }

    $original = webpPut('pages/portrait.jpg', webpExifJpegBytes(6));

    $result = app(ImageOptimizer::class)->optimize($original);

    $size = getimagesize(Storage::disk('public')->path($result));
    expect([$size[0], $size[1]])->toBe([200, 300]);
});

it('MediaUploader range le WebP et renseigne chemin, type MIME et taille', function () {
    $file = UploadedFile::fake()->createWithContent('photo.jpg', webpJpegBytes());

    $media = app(MediaUploader::class)->fromUpload($file, 'pages', ['type' => 'image']);

    expect($media->path)->toEndWith('.webp')
        ->and($media->mime_type)->toBe('image/webp')
        ->and($media->original_name)->toBe('photo.jpg')
        ->and($media->size)->toBe(Storage::disk('public')->size($media->path));
    Storage::disk('public')->assertExists($media->path);
    // L'original .jpg a disparu (la miniature et les variantes du job ProcessMediaImage s'ajoutent, d'ou pas de comptage).
    Storage::disk('public')->assertMissing('pages/'.pathinfo($media->path, PATHINFO_FILENAME).'.jpg');
});

it('MediaUploader execute le hook sur le fichier d origine, avant la conversion', function () {
    $seen = [];
    $file = UploadedFile::fake()->createWithContent('photo.jpg', webpJpegBytes());

    $media = app(MediaUploader::class)->fromUpload($file, 'pages', [], function (string $path) use (&$seen): array {
        $seen = ['path' => $path, 'exists' => Storage::disk('public')->exists($path)];

        return ['width' => 600, 'height' => 400];
    });

    expect($seen['path'])->toEndWith('.jpg')
        ->and($seen['exists'])->toBeTrue()
        ->and($media->path)->toEndWith('.webp')
        ->and($media->width)->toBe(600)
        ->and($media->height)->toBe(400);
});

it('MediaUploader laisse un GIF tel quel', function () {
    $file = UploadedFile::fake()->createWithContent('anim.gif', webpBytes(fn () => imagegif(webpNoisyImage(50, 50))));

    $media = app(MediaUploader::class)->fromUpload($file, 'pages');

    expect($media->path)->toEndWith('.gif')
        ->and($media->mime_type)->toBe('image/gif');
});

it('MediaUploader ne convertit pas un PDF', function () {
    $file = UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.4\n%%EOF");

    $media = app(MediaUploader::class)->fromUpload($file, 'pdfs');

    expect($media->path)->toEndWith('.pdf')
        ->and(Media::count())->toBe(1);
});

it('l image de couverture est filigranee puis convertie en WebP', function () {
    $watermarked = null;
    $this->mock(WatermarkService::class, function ($mock) use (&$watermarked) {
        $mock->shouldReceive('watermarkImage')->once()->andReturnUsing(function (string $path) use (&$watermarked) {
            $watermarked = $path;

            return true;
        });
    });

    $request = Request::create('/', 'POST', ['apply_watermark_cover_image' => '1'], [], [
        'image' => UploadedFile::fake()->createWithContent('cover.jpg', webpJpegBytes()),
    ]);

    $path = app(MediaSyncService::class)->syncCoverImage($request, null);

    expect($watermarked)->toEndWith('.jpg')
        ->and($path)->toEndWith('.webp')
        ->and($path)->toStartWith('articles/');
    Storage::disk('public')->assertExists($path);
    Storage::disk('public')->assertMissing($watermarked);
});
