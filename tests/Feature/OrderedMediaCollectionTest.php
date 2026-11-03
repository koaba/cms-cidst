<?php

use App\Models\Article;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

it('cree les Media a la suite de ceux deja attaches, avec nom, type MIME et taille', function () {
    $article = Article::factory()->create();
    $article->media()->attach(Media::factory()->create()->id, ['order' => 0]);

    $article->attachUploadedFiles([
        UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
    ], 'articles/pdfs');

    $attached = $article->fresh()->media->whereIn('original_name', ['a.pdf', 'b.pdf']);

    expect($attached->pluck('pivot.order', 'original_name')->all())->toBe(['a.pdf' => 1, 'b.pdf' => 2])
        ->and($attached->pluck('mime_type')->unique()->all())->toBe(['application/pdf'])
        ->and($attached->every(fn (Media $m) => $m->size === Storage::disk('public')->size($m->path) && str_starts_with($m->path, 'articles/pdfs/')))->toBeTrue();
});

it('relit la taille du fichier apres le hook', function () {
    $article = Article::factory()->create();

    $article->attachUploadedFiles(
        [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
        'articles/pdfs',
        fn (string $path) => Storage::disk('public')->put($path, str_repeat('x', 4321)),
    );

    expect($article->fresh()->media->firstWhere('original_name', 'a.pdf')->size)->toBe(4321);
});

it('enregistre la miniature envoyee par le navigateur sous le dossier thumbnails', function () {
    $article = Article::factory()->create();

    $article->attachUploadedFiles(
        [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
        'articles/pdfs',
        null,
        [['name' => 'doc.pdf', 'thumbnail' => 'data:image/png;base64,'.base64_encode('PNGDATA')]],
    );

    $media = $article->fresh()->media->firstWhere('original_name', 'doc.pdf');

    expect($media->thumbnail_path)->toStartWith('articles/pdfs/thumbnails/pdf_')
        ->and($media->thumbnail_path)->toEndWith('.png');
    Storage::disk('public')->assertExists($media->thumbnail_path);
});

it('prefere la miniature du generateur serveur a celle du navigateur', function () {
    $article = Article::factory()->create();

    $article->attachUploadedFiles(
        [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
        'articles/pdfs',
        null,
        [['name' => 'doc.pdf', 'thumbnail' => 'data:image/png;base64,'.base64_encode('PNGDATA')]],
        fn (string $path) => 'articles/pdfs/thumbnails/serveur.jpg',
    );

    expect($article->fresh()->media->firstWhere('original_name', 'doc.pdf')->thumbnail_path)
        ->toBe('articles/pdfs/thumbnails/serveur.jpg');
});

it('laisse thumbnail_path a null sans miniature ni generateur', function () {
    $article = Article::factory()->create();

    $article->attachUploadedFiles([UploadedFile::fake()->image('a.jpg')], 'articles/gallery');

    expect($article->fresh()->media->firstWhere('original_name', 'a.jpg')->thumbnail_path)->toBeNull();
});

it('ne laisse ni fichier ni Media orphelin quand le hook echoue', function () {
    $article = Article::factory()->create();

    expect(fn () => $article->attachUploadedFiles(
        [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
        'articles/pdfs',
        fn (string $path) => throw new LogicException('echec du hook'),
    ))->toThrow(LogicException::class, 'echec du hook');

    expect(Media::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});
