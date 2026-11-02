<?php

use App\Blocks\Rules\VideoRules;
use Illuminate\Support\Facades\Validator;

it('refuse un lien video hors YouTube et Vimeo', function (string $url) {
    $errors = Validator::make(['source_type' => 'url', 'url' => $url], (new VideoRules)->rules(true))->errors()->keys();

    expect($errors)->toContain('url');
})->with([
    'javascript' => ['javascript:alert(1)'],
    'autre site' => ['https://example.com/video'],
    'identifiant trop court' => ['https://youtube.com/watch?v=abc'],
]);

it('accepte un lien YouTube ou Vimeo', function (string $url) {
    $errors = Validator::make(['source_type' => 'url', 'url' => $url], (new VideoRules)->rules(true))->errors()->keys();

    expect($errors)->not->toContain('url');
})->with([
    'youtube' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
    'vimeo' => ['https://vimeo.com/76979871'],
]);

it('ignore le champ url quand la source est un fichier', function () {
    $errors = Validator::make(['source_type' => 'upload', 'url' => 'https://example.com/x'], (new VideoRules)->rules(true))->errors()->keys();

    expect($errors)->not->toContain('url');
});
