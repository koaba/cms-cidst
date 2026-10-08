<?php

use App\Models\Media;

function renderImageBlock(array $attributes): string
{
    $media = Media::make(['path' => 'pages/x.jpg', 'type' => 'image'] + $attributes);

    return view('pages.blocks._image', ['media' => collect([$media]), 'data' => ['alt' => 'x']])->render();
}

it('ecrit width et height quand les dimensions sont connues', function () {
    $html = renderImageBlock(['width' => 120, 'height' => 80]);

    expect($html)->toContain('width="120"')
        ->and($html)->toContain('height="80"');
});

it('omet width et height quand les dimensions sont inconnues', function () {
    $html = renderImageBlock([]);

    expect($html)->not->toContain('width=')
        ->and($html)->not->toContain('height=');
});
