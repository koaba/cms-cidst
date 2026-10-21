<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\Slider;
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

it('ne supprime pas un media encore couverture d un article quand son slider est supprime', function () {
    UploadedFile::fake()->image('shared.jpg', 600, 300)->storeAs('sliders', 'shared.jpg', 'public');

    $media = Media::create([
        'path' => 'sliders/shared.jpg',
        'original_name' => 'shared.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);

    $article = Article::factory()->create();
    $article->coverMedia()->associate($media)->save();

    $slider = Slider::create([
        'title' => 'Slider partage',
        'image' => $media->path,
        'order' => 0,
        'is_active' => true,
    ]);
    $slider->media()->attach($media->id, ['order' => 0]);

    $this->actingAs($this->admin)->delete(route('admin.sliders.destroy', $slider));

    $this->assertDatabaseMissing('sliders', ['id' => $slider->id]);
    expect(Media::find($media->id))->not->toBeNull()
        ->and($article->fresh()->cover_media_id)->toBe($media->id);
});

it('ne supprime pas un media de galerie encore couverture d un autre article', function () {
    $media = Media::create([
        'path' => 'articles/gallery-shared.jpg',
        'original_name' => 'gallery-shared.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);

    $galleryArticle = Article::factory()->create();
    $galleryArticle->media()->attach($media->id, ['order' => 0]);

    $coverArticle = Article::factory()->create();
    $coverArticle->coverMedia()->associate($media)->save();

    $galleryArticle->detachOwnedMedia([$media->id]);

    expect(Media::find($media->id))->not->toBeNull()
        ->and($coverArticle->fresh()->cover_media_id)->toBe($media->id);
});

it('conserve l ancienne couverture remplacee si un slider l utilise encore', function () {
    UploadedFile::fake()->image('old.jpg', 600, 300)->storeAs('articles', 'old.jpg', 'public');

    $old = Media::create([
        'path' => 'articles/old.jpg',
        'original_name' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'image',
    ]);

    $article = Article::factory()->create(['image' => 'articles/old.jpg']);
    $article->coverMedia()->associate($old)->save();

    $slider = Slider::create(['title' => 'S', 'image' => $old->path, 'order' => 0, 'is_active' => true]);
    $slider->media()->attach($old->id, ['order' => 0]);

    $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'content' => $article->content,
        'image' => UploadedFile::fake()->image('new.jpg', 800, 400),
    ])->assertSessionHasNoErrors();

    expect(Media::find($old->id))->not->toBeNull();
});
