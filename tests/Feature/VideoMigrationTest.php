<?php

use App\Models\Article;
use App\Models\Video;

it('migre les videos existantes vers le systeme Media sans perte', function () {
    $article = Article::factory()->create();

    Video::create([
        'videoable_type' => Article::class,
        'videoable_id' => $article->id,
        'source_type' => 'external',
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'title' => 'Vidéo de test',
        'order' => 0,
        'apply_watermark' => false,
    ]);

    $this->artisan('app:migrate-videos-to-media')->assertSuccessful();

    $article->refresh();

    expect($article->videoMedia()->count())->toBe(1);
    $media = $article->videoMedia()->first();
    expect($media->type)->toBe('video');
    expect($media->source_type)->toBe('external');
    expect($media->embed_url)->toContain('youtube.com/embed');
});
