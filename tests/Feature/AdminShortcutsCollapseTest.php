<?php

it('affiche les raccourcis dans un bloc deroulant ferme par defaut', function () {
    $html = $this->actingAs(adminUser())->get('/admin')->assertOk()->getContent();

    expect($html)->toContain('<details data-shortcuts')
        ->and($html)->toContain('<summary')
        ->and(preg_match('/<details data-shortcuts[^>]*\sopen/', $html))->toBe(0);
});
