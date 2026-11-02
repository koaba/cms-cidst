<?php

it('affiche une iframe pour une url YouTube', function () {
    $html = view('pages.blocks._video', [
        'data' => ['source_type' => 'url', 'url' => 'https://youtu.be/dQw4w9WgXcQ'],
        'media' => collect(),
    ])->render();

    expect($html)->toContain('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"');
});

it('n\'affiche aucune iframe pour une url hors liste blanche', function (string $url) {
    $html = view('pages.blocks._video', [
        'data' => ['source_type' => 'url', 'url' => $url],
        'media' => collect(),
    ])->render();

    expect($html)->not->toContain('<iframe');
})->with([
    'javascript' => ['javascript:alert(1)'],
    'autre site' => ['https://example.com/page'],
]);
