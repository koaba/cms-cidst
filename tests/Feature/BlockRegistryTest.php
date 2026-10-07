<?php

use App\Blocks\BlockRegistry;
use App\Contracts\BlockRules;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('enregistre chaque type de config/page_blocks.php', function () {
    $configured = array_merge(
        array_keys(config('page_blocks.types')),
        array_keys(config('page_blocks.internal_types'))
    );

    expect(BlockRegistry::types())->toEqualCanonicalizing($configured);
});

it('retourne une instance de BlockRules pour chaque type', function () {
    foreach (BlockRegistry::types() as $type) {
        expect(BlockRegistry::rulesFor($type))->toBeInstanceOf(BlockRules::class);
    }
});

it('leve une 404 pour un type inconnu', function () {
    try {
        BlockRegistry::rulesFor('inconnu');
        $this->fail('Une 404 etait attendue.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(404);
    }
});

it('enregistre chaque type de nestable_in_columns', function () {
    $nestable = config('page_blocks.nestable_in_columns');

    expect($nestable)->not->toBeEmpty()
        ->and(array_diff($nestable, BlockRegistry::types()))->toBe([])
        ->and(array_diff($nestable, array_keys(config('page_blocks.types'))))->toBe([])
        ->and(array_intersect($nestable, ['colonnes', 'accordeon_item']))->toBe([]);
});
