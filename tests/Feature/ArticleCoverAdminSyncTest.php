<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Queue::fake();
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

function adminArticleWithStoredCover(): Article
{
    UploadedFile::fake()->image('old.jpg', 600, 300)->storeAs('articles', 'old.jpg', 'public');
    $media = Media::create([
        'path' => 'articles/old.jpg',
        'original_name' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);
    $article = Article::factory()->create(['image' => 'articles/old.jpg']);
    $article->coverMedia()->associate($media)->save();

    return $article;
}

it('cree le media de couverture a la creation de l article', function () {
    $this->actingAs($this->admin)->post(route('admin.articles.store'), [
        'title' => 'Nouvel article',
        'content' => 'Contenu',
        'image' => UploadedFile::fake()->image('cover.jpg', 800, 400),
    ])->assertSessionHasNoErrors();

    $media = Article::firstOrFail()->coverMedia;

    expect($media)->not->toBeNull()
        ->and($media->type)->toBe('image')
        ->and($media->width)->toBe(800)
        ->and($media->height)->toBe(400);
});

it('cree le media de couverture a la modification', function () {
    $article = Article::factory()->create(['image' => null]);

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'image' => UploadedFile::fake()->image('cover.jpg', 800, 400),
    ])->assertSessionHasNoErrors();

    $media = $article->fresh()->coverMedia;

    expect($media)->not->toBeNull()
        ->and($media->path)->toStartWith('articles/')
        ->and($media->width)->toBe(800);
});

it('remplace la couverture sans laisser de media orphelin', function () {
    $article = adminArticleWithStoredCover();
    $oldId = $article->cover_media_id;

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'image' => UploadedFile::fake()->image('new.jpg', 800, 400),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('media', ['id' => $oldId]);
    Storage::disk('public')->assertMissing('articles/old.jpg');

    $new = $article->fresh()->coverMedia;
    expect($new)->not->toBeNull()->and($new->id)->not->toBe($oldId);
});

it('supprime le media de couverture avec l article', function () {
    $article = adminArticleWithStoredCover();
    $mediaId = $article->cover_media_id;

    $this->actingAs($this->admin)->delete(route('admin.articles.destroy', $article));

    $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    $this->assertDatabaseMissing('media', ['id' => $mediaId]);
});
