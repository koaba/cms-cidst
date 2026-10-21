<?php

use App\Services\Theme\ColorConverter;

function colorConvOrNull(callable $fn): mixed
{
    try {
        return $fn();
    } catch (Throwable) {
        return null;
    }
}

function colorConvThrows(callable $fn): string
{
    try {
        $fn();

        return 'no-exception';
    } catch (Throwable $e) {
        return $e::class;
    }
}

/** @return array{float, float, float} Sentinelle -1 si la chaine n'a pas le format DaisyUI. */
function oklchParts(mixed $value): array
{
    if (! is_string($value) || ! preg_match('/^([\d.]+)%\s+([\d.]+)\s+([\d.]+)$/', $value, $m)) {
        return [-1.0, -1.0, -1.0];
    }

    return [(float) $m[1], (float) $m[2], (float) $m[3]];
}

it('convertit un hex en triplet rgb pour Tailwind, majuscules ou minuscules', function () {
    expect(colorConvOrNull(fn () => (new ColorConverter)->rgbTriplet('#C41E2A')))->toBe('196 30 42')
        ->and(colorConvOrNull(fn () => (new ColorConverter)->rgbTriplet('#c41e2a')))->toBe('196 30 42');
});

it('convertit le primary de reference au format oklch de DaisyUI', function () {
    [$l, $c, $h] = oklchParts(colorConvOrNull(fn () => (new ColorConverter)->daisyOklch('#C41E2A')));

    expect($l)->toEqualWithDelta(52.8615, 0.01)
        ->and($c)->toEqualWithDelta(0.197918, 0.0005)
        ->and($h)->toEqualWithDelta(24.5942, 0.05);
});

it('convertit le secondary de reference au format oklch de DaisyUI', function () {
    [$l, $c, $h] = oklchParts(colorConvOrNull(fn () => (new ColorConverter)->daisyOklch('#17181A')));

    expect($l)->toEqualWithDelta(20.8789, 0.01)
        ->and($c)->toEqualWithDelta(0.004235, 0.0005)
        ->and($h)->toEqualWithDelta(264.477, 0.5);
});

it('convertit le blanc en 100% 0 0', function () {
    [$l, $c] = oklchParts(colorConvOrNull(fn () => (new ColorConverter)->daisyOklch('#FFFFFF')));

    expect($l)->toEqualWithDelta(100.0, 0.01)
        ->and($c)->toEqualWithDelta(0.0, 0.0005);
});

it('calcule la couleur de contraste d une couleur sombre comme DaisyUI (80% vers le blanc)', function () {
    [$l, $c, $h] = oklchParts(colorConvOrNull(fn () => (new ColorConverter)->contentOklch('#C41E2A')));

    expect($l)->toEqualWithDelta(90.5723, 0.01)
        ->and($c)->toEqualWithDelta(0.039584, 0.0005)
        ->and($h)->toEqualWithDelta(24.5942, 0.05);
});

it('refuse une valeur qui n est pas un hex a 6 chiffres', function () {
    expect(colorConvThrows(fn () => (new ColorConverter)->rgbTriplet('javascript:alert(1)')))->toBe(InvalidArgumentException::class)
        ->and(colorConvThrows(fn () => (new ColorConverter)->daisyOklch('#FFF')))->toBe(InvalidArgumentException::class);
});

it('calcule la couleur de contraste du blanc en melangeant 80% vers le noir', function () {
    [$l, $c] = oklchParts(colorConvOrNull(fn () => (new ColorConverter)->contentOklch('#FFFFFF')));

    expect($l)->toEqualWithDelta(20.0, 0.01)
        ->and($c)->toEqualWithDelta(0.0, 0.0005);
});

it('garde la teinte et divise luminosite et chroma par cinq pour une couleur claire', function () {
    [$l0, $c0, $h0] = oklchParts(colorConvOrNull(fn () => (new ColorConverter)->daisyOklch('#FFD700')));
    [$l, $c, $h] = oklchParts(colorConvOrNull(fn () => (new ColorConverter)->contentOklch('#FFD700')));

    expect($l0)->toBeGreaterThan(0.0)
        ->and($l)->toEqualWithDelta($l0 * 0.2, 0.01)
        ->and($c)->toEqualWithDelta($c0 * 0.2, 0.0005)
        ->and($h)->toEqualWithDelta($h0, 0.05);
});
