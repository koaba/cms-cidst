<?php

use App\Services\PageBuilder\BlockSyncService;

it('derive les types editables du catalogue, dans l ordre', function () {
    expect(BlockSyncService::editableTypes())->toBe(['texte', 'banniere_hero', 'image', 'bouton', 'citation', 'separateur']);
});

it('derive les types a image unique du catalogue', function () {
    expect(BlockSyncService::mediaTypes())->toBe(['banniere_hero', 'image']);
});
