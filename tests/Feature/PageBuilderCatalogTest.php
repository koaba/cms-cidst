<?php

use App\Blocks\BlockRegistry;
use App\Contracts\DeclaresMediaFields;
use App\Services\PageBuilder\BlockFieldCatalog;
use App\Services\PageBuilder\BlockSyncService;

it('decrit exactement les types editables', function () {
    expect(array_keys(BlockFieldCatalog::all()))->toEqualCanonicalizing(BlockSyncService::editableTypes());
});

it('a les memes cles de champs que les regles de validation (hors media et design)', function (string $type) {
    $rules = BlockRegistry::rulesFor($type);
    $media = $rules instanceof DeclaresMediaFields ? $rules->mediaFields() : [];

    $ruleKeys = array_values(array_filter(
        array_diff(array_keys($rules->rules(false)), $media),
        fn (string $key) => $key !== 'design' && ! str_starts_with($key, 'design.'),
    ));
    $fieldKeys = array_column(BlockFieldCatalog::for($type)['fields'], 'key');

    expect($fieldKeys)->toEqualCanonicalizing($ruleKeys);
})->with(fn () => BlockSyncService::editableTypes());

it('fournit une valeur par defaut pour chaque champ', function (string $type) {
    $definition = BlockFieldCatalog::for($type);

    expect(array_keys($definition['defaults']))
        ->toEqualCanonicalizing(array_column($definition['fields'], 'key'));
})->with(fn () => BlockSyncService::editableTypes());

it('refuse un type absent du catalogue', function () {
    BlockFieldCatalog::for('colonnes');
})->throws(InvalidArgumentException::class);
