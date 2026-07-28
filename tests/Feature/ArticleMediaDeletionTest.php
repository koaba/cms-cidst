<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

it('supprime les fichiers physiques et les entrées media quand un article est supprimé', function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $article = Article::create([
        'title' => 'Article avec médias',
        'slug' => 'article-avec-medias',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $file = UploadedFile::fake()->image('test.jpg');
    $path = $file->store('articles/gallery', 'public');
    $media = Media::create([
        'path' => $path,
        'original_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);
    $article->media()->attach($media->id, ['order' => 0]);

    Storage::disk('public')->assertExists($path);

    $this->actingAs($user)->delete("/admin/articles/{$article->id}");

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($path);
});

it('ne supprime pas un média encore utilisé par un autre article', function () {
    Storage::fake('public');
    Role::create(['name' => 'Super Admin']);
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $articleA = Article::create([
        'title' => 'Article A',
        'slug' => 'article-a',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $articleB = Article::create([
        'title' => 'Article B',
        'slug' => 'article-b',
        'content' => 'Contenu',
        'user_id' => $user->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $file = UploadedFile::fake()->image('shared.jpg');
    $path = $file->store('articles/gallery', 'public');
    $media = Media::create([
        'path' => $path,
        'original_name' => 'shared.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
    ]);

    // Le même média est attaché aux deux articles
    $articleA->media()->attach($media->id, ['order' => 0]);
    $articleB->media()->attach($media->id, ['order' => 0]);

    $this->actingAs($user)->delete("/admin/articles/{$articleA->id}");

    // Le média doit survivre, car il est toujours utilisé par l'article B
    $this->assertDatabaseHas('media', ['id' => $media->id]);
    Storage::disk('public')->assertExists($path);

    // On peut maintenant supprimer l'article B aussi
    $this->actingAs($user)->delete("/admin/articles/{$articleB->id}");

    // Cette fois le média doit vraiment disparaître
    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    Storage::disk('public')->assertMissing($path);
});