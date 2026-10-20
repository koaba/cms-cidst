<?php

it('n ecrit pas la declaration xml comme une balise php brute dans la vue', function () {
    $source = file_get_contents(resource_path('views/sitemap/index.blade.php'));

    expect(ltrim($source))->not->toStartWith('<?');
});

it('rend un sitemap qui commence par la declaration xml', function () {
    $body = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($body)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>');
});
