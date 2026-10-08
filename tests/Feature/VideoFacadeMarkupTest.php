<?php

it('donne la classe video-facade au conteneur de la facade video', function () {
    $blade = file_get_contents(resource_path('views/public/articles/show.blade.php'));

    expect($blade)->toContain('data-embed-url="{{ $video->embed_url }}"')
        ->and($blade)->toMatch('/<div\s[^>]*\bclass="[^"]*\bvideo-facade\b[^"]*"[^>]*data-embed-url/s')
        ->and($blade)->not->toMatch('/\bcla\s+ss=/');
});
