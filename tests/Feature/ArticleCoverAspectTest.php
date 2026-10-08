<?php

it('impose un ratio aspect-* sur la couverture d\'article', function () {
    $blade = file_get_contents(resource_path('views/public/articles/show.blade.php'));

    preg_match_all('/<img\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/s', $blade, $matches);

    $cover = collect($matches[0])
        ->map(fn ($tag) => preg_replace('/\s+/', ' ', $tag))
        ->first(fn ($tag) => str_contains($tag, 'fetchpriority="high"'));

    expect($cover)->not->toBeNull()
        ->and($cover)->toMatch('/class="[^"]*\baspect-/');
});
