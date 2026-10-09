<?php

it('affiche la galerie de l\'article via x-media-img', function () {
    $blade = file_get_contents(resource_path('views/public/articles/show.blade.php'));

    expect(substr_count($blade, '<x-media-img :media="$media"'))->toBe(2)
        ->and($blade)->toContain('sizes="(min-width: 448px) 448px, 100vw"')
        ->and($blade)->toContain('sizes="(min-width: 640px) 33vw, 50vw"')
        ->and(substr_count($blade, ':alt="$article->title.'))->toBe(2)
        ->and(preg_match_all('/:onclick="[^"]*gallery[^"]*\$loop->index/', $blade))->toBe(2)
        ->and($blade)->not->toContain('<img src="{{ Storage::url($media->path) }}"');
});
