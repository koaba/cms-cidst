<?php

it('garde le logo leger et a une taille raisonnable', function () {
    $path = public_path('images/logo-cidst.png');
    [$width, $height] = getimagesize($path);

    expect(filesize($path))->toBeLessThan(40 * 1024)
        ->and($width)->toBeLessThanOrEqual(400)
        ->and($height)->toBeLessThanOrEqual(400);
});
