<?php

it('lie chaque label du formulaire hero a son champ', function () {
    $html = file_get_contents(resource_path('views/admin/pages/blocks/partials/_banniere_hero.blade.php'));

    preg_match_all('/<label\b[^>]*\bfor="([^"]+)"/', $html, $labels);
    preg_match_all('/<(?:input|textarea|select)\b[^>]*\bid="([^"]+)"/', $html, $fields);

    expect($labels[1])->toHaveCount(7)
        ->and(array_diff($labels[1], $fields[1]))->toBe([]);
});
