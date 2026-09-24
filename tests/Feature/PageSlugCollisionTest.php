<?php

use App\Models\Page;

it('nomme le premier slug en collision avec le suffixe -1, pas -0', function () {
    $first = Page::factory()->create(['title' => 'Ma Page Test']);
    $second = Page::factory()->create(['title' => 'Ma Page Test']);

    expect($first->slug)->toBe('ma-page-test')
        ->and($second->slug)->toBe('ma-page-test-1');
});