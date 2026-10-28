<?php

use App\Blocks\BlockRegistry;
use App\Contracts\DeclaresMediaFields;
use App\Services\PageBuilder\BlockFieldCatalog;
use App\Services\PageBuilder\BlockSyncService;

it('decrit exactement les types editables', function () {
    expect(array_keys(BlockFieldCatalog::all()))->toEqualCanonicalizing(BlockSyncService::EDITABLE_TYPES);
});

it('a les memes cles de champs que les regles de validation (hors media)', function (string $type) {
    $rules = BlockRegistry::rulesFor($type);
    $media = $rules instanceof DeclaresMediaFields ? $rules->mediaFields() : [];

    $ruleKeys = array_values(array_diff(array_keys($rules->rules(false)), $media));
    $fieldKeys = array_column(BlockFieldCatalog::for($type)['fields'], 'key');

    expect($fieldKeys)->toEqualCanonicalizing($ruleKeys);
})->with(BlockSyncService::EDITABLE_TYPES);

it('fournit une valeur par defaut pour chaque champ', function (string $type) {
    $definition = BlockFieldCatalog::for($type);

    expect(array_keys($definition['defaults']))
        ->toEqualCanonicalizing(array_column($definition['fields'], 'key'));
})->with(BlockSyncService::EDITABLE_TYPES);

it('refuse un type absent du catalogue', function () {
    BlockFieldCatalog::for('colonnes');
})->throws(InvalidArgumentException::class);
