<?php

it('verrouille la plateforme PHP de composer sur 8.3.0', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composer['config']['platform']['php'] ?? null)->toBe('8.3.0');
});

it('declare ext-gd comme dependance obligatoire', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composer['require'])->toHaveKey('ext-gd');
});
