<?php

use Illuminate\Support\Facades\Schema;

it('indexe site_visits par (visited_at, path) pour le classement des pages', function () {
    expect(Schema::hasIndex('site_visits', ['visited_at', 'path']))->toBeTrue();
});

it('n entretient plus l index visited_at seul, couvert par les index composites', function () {
    expect(Schema::hasIndex('site_visits', ['visited_at']))->toBeFalse()
        ->and(Schema::hasIndex('site_visits', ['visited_at', 'visitor_hash']))->toBeTrue();
});
