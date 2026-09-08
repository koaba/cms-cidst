<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

it('cree une video uploadee via le formulaire', function () {
    $article = Article::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'source_type' => 'upload',
                'file' => UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4'),
                'title' => 'Ma video',
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    $response->assertSessionDoesntHaveErrors();

    $article->refresh();
    expect($article->videoMedia)->toHaveCount(1);

    $media = $article->videoMedia->first();
    expect($media->source_type)->toBe('upload');
    expect($media->original_name)->toBe('Ma video');
    Storage::disk('public')->assertExists($media->path);
});

it('cree une video externe via le formulaire', function () {
    $article = Article::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'source_type' => 'external',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'title' => 'Video externe',
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    $response->assertSessionDoesntHaveErrors();

    $article->refresh();
    expect($article->videoMedia)->toHaveCount(1);

    $media = $article->videoMedia->first();
    expect($media->source_type)->toBe('external');
    expect($media->embed_url)->toContain('youtube.com/embed');
});

it('rejette une nouvelle video upload sans fichier fourni', function () {
    $article = Article::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'source_type' => 'upload',
                'title' => 'Nouvelle video sans fichier',
            ],
        ],
    ]);

    $response->assertSessionHasErrors('videos.0.file');
});

it('rejette une nouvelle video externe sans url fournie', function () {
    $article = Article::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'source_type' => 'external',
                'title' => 'Nouvelle video sans url',
            ],
        ],
    ]);

    $response->assertSessionHasErrors('videos.0.url');
});

it('permet de modifier un article sans re-uploader ses videos existantes', function () {
    $article = Article::factory()->create();

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'upload',
        'path' => 'articles/videos/existant.mp4',
        'original_name' => 'Video existante',
        'apply_watermark' => false,
    ]);
    $media = Media::create([
        'type' => 'video',
        'source_type' => 'upload',
        'path' => 'articles/videos/existant.mp4',
        'original_name' => 'Video existante',
        'apply_watermark' => false,
    ]);
    +Storage::disk('public')->put($media->path, 'contenu video factice');
    $article->media()->attach($media->id, ['order' => 0]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => 'Titre modifie',
        'content' => $article->content,
        'videos' => [
            [
                'id' => $media->id,
                'source_type' => 'upload',
                'title' => 'Video existante',
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.articles.index'));
    $response->assertSessionDoesntHaveErrors();
    expect($article->fresh()->title)->toBe('Titre modifie');

    // Le fichier n'a pas ete touche puisqu'aucun nouveau fichier n'a ete fourni
    Storage::disk('public')->assertExists('articles/videos/existant.mp4');
});

it('remplace le fichier d\'une video uploadee existante', function () {
    $article = Article::factory()->create();

    $oldFile = UploadedFile::fake()->create('ancien.mp4', 300, 'video/mp4');
    $oldPath = $oldFile->store('articles/videos', 'public');

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'upload',
        'path' => $oldPath,
        'original_name' => 'Video existante',
        'apply_watermark' => false,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'id' => $media->id,
                'source_type' => 'upload',
                'file' => UploadedFile::fake()->create('nouveau.mp4', 400, 'video/mp4'),
                'title' => 'Video existante',
            ],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();

    $media->refresh();
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($media->path);
    expect($media->path)->not->toBe($oldPath);
});

it('change l\'url d\'une video externe existante', function () {
    $article = Article::factory()->create();

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'external',
        'url' => 'https://www.youtube.com/watch?v=aaaaaaaaaaa',
        'original_name' => 'Video externe',
        'apply_watermark' => false,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'id' => $media->id,
                'source_type' => 'external',
                'url' => 'https://www.youtube.com/watch?v=bbbbbbbbbbb',
                'title' => 'Video externe',
            ],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($media->fresh()->url)->toBe('https://www.youtube.com/watch?v=bbbbbbbbbbb');
});

it('active le filigrane sur une video existante', function () {
    $article = Article::factory()->create();

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'external',
        'url' => 'https://www.youtube.com/watch?v=ccccccccccc',
        'original_name' => 'Video externe',
        'apply_watermark' => false,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'id' => $media->id,
                'source_type' => 'external',
                'apply_watermark' => '1',
                'title' => 'Video externe',
            ],
        ],
    ]);

    expect($media->fresh()->apply_watermark)->toBeTrue();
});

it('supprime une video via delete_videos', function () {
    $article = Article::factory()->create();

    $file = UploadedFile::fake()->create('a-supprimer.mp4', 300, 'video/mp4');
    $path = $file->store('articles/videos', 'public');

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'upload',
        'path' => $path,
        'original_name' => 'A supprimer',
        'apply_watermark' => false,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'delete_videos' => [$media->id],
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($article->fresh()->videoMedia)->toHaveCount(0);
    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($path);
});

it('rejette la suppression d\'une video appartenant a un autre article', function () {
    $articleA = Article::factory()->create();
    $articleB = Article::factory()->create();

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'external',
        'url' => 'https://www.youtube.com/watch?v=ddddddddddd',
        'original_name' => 'Video de A',
        'apply_watermark' => false,
    ]);
    $articleA->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $articleB), [
        'title' => $articleB->title,
        'content' => $articleB->content,
        'delete_videos' => [$media->id],
    ]);

    $response->assertSessionHasErrors('delete_videos.0');
});

it('supprime les fichiers video quand un article est supprime', function () {
    $article = Article::factory()->create();

    $file = UploadedFile::fake()->create('a-supprimer.mp4', 300, 'video/mp4');
    $path = $file->store('articles/videos', 'public');

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'upload',
        'path' => $path,
        'original_name' => 'Video',
        'apply_watermark' => false,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $this->actingAs($this->admin)->delete(route('admin.articles.destroy', $article));

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($path);
});

it('conserve une video externe reutilisee par un autre article a la suppression', function () {
    $articleA = Article::factory()->create();
    $articleB = Article::factory()->create();

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'external',
        'url' => 'https://www.youtube.com/watch?v=eeeeeeeeeee',
        'original_name' => 'Video partagee',
        'apply_watermark' => false,
    ]);
    $articleA->media()->attach($media->id, ['order' => 0]);
    $articleB->media()->attach($media->id, ['order' => 0]);

    $this->actingAs($this->admin)->delete(route('admin.articles.destroy', $articleA));

    $this->assertDatabaseHas('media', ['id' => $media->id]);
    expect($articleB->fresh()->videoMedia)->toHaveCount(1);
});

it('ne recree pas une video quand elle est a la fois dans videos et delete_videos', function () {
    $article = Article::factory()->create();

    $media = Media::create([
        'type' => 'video',
        'source_type' => 'external',
        'url' => 'https://www.youtube.com/watch?v=fffffffffff',
        'original_name' => 'A supprimer',
        'apply_watermark' => false,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    $response = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'videos' => [
            [
                'id' => $media->id,
                'source_type' => 'external',
                'url' => 'https://www.youtube.com/watch?v=fffffffffff',
                'title' => 'A supprimer',
            ],
        ],
        'delete_videos' => [$media->id],
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($article->fresh()->videoMedia)->toHaveCount(0);
    $this->assertDatabaseMissing('media', ['id' => $media->id]);
});
