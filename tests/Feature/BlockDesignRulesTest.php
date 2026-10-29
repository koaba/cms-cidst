<?php

use App\Blocks\Rules\TexteRules;
use Illuminate\Support\Facades\Validator;

it('refuse un alignement hors liste', function () {
    $v = Validator::make(
        ['content' => 'x', 'design' => ['align' => 'diagonal']],
        (new TexteRules)->rules(true)
    );

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->keys())->toContain('design.align');
});

it('conserve un alignement valide', function (string $align) {
    $v = Validator::make(
        ['content' => 'x', 'design' => ['align' => $align]],
        (new TexteRules)->rules(true)
    );

    expect($v->fails())->toBeFalse()
        ->and($v->validated()['design']['align'])->toBe($align);
})->with(['left', 'center', 'right']);

it('accepte un bloc sans design et ignore une cle de design inconnue', function () {
    $v = Validator::make(
        ['content' => 'x', 'design' => ['inconnue' => 'y']],
        (new TexteRules)->rules(true)
    );

    expect($v->fails())->toBeFalse()
        ->and($v->validated()['design'] ?? [])->not->toHaveKey('inconnue');
});