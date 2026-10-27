<?php

it('masque les menus PDF a un utilisateur Publication', function () {
    $html = $this->actingAs(publicationUser())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)
        ->not->toContain(route('admin.pdf-documents.index'))
        ->not->toContain(route('admin.pdf-categories.index'));
});

it('garde les menus PDF pour Super Admin', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)
        ->toContain(route('admin.pdf-documents.index'))
        ->toContain(route('admin.pdf-categories.index'));
});

it('garde les menus communs pour Publication', function () {
    $html = $this->actingAs(publicationUser())->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)
        ->toContain(route('admin.articles.index'))
        ->toContain(route('admin.pages.index'));
});
