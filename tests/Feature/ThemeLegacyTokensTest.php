<?php

// Lot 3d-3 : les anciens noms cidst-red et cidst-ink sont retires. Les couleurs se nomment
// comme leur role : cidst-primary et cidst-secondary.

function themeLegacySources(): array
{
    return collect(['resources/views', 'resources/js', 'resources/css'])
        ->flatMap(fn (string $directory) => File::allFiles(base_path($directory)))
        ->map(fn ($file) => $file->getPathname())
        ->all();
}

function themeLegacyRead(string $relative): string
{
    return (string) file_get_contents(base_path($relative));
}

it('n utilise plus l ancien nom cidst-:name nulle part dans les vues, le js et le css', function (string $name) {
    $offenders = collect(themeLegacySources())
        ->filter(fn (string $path) => preg_match('/cidst-'.$name.'(?![\w-])/', (string) file_get_contents($path)) === 1)
        ->map(fn (string $path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
})->with(['red', 'ink']);

it('ne declare plus les alias red et ink dans les jetons Tailwind', function () {
    expect(themeLegacyRead('tailwind.tokens.js'))->not->toMatch('/^\s*(?:red|ink)\s*:/m');
});

it('utilise les jetons primary et secondary dans les vues', function (string $view, string $class) {
    expect(themeLegacyRead($view))->toContain($class);
})->with([
    'menu' => ['resources/views/components/main-menu.blade.php', 'cidst-primary'],
    'bandeau' => ['resources/views/components/news-ticker.blade.php', 'cidst-primary'],
    'recherche' => ['resources/views/public/search/index.blade.php', 'cidst-primary'],
    'banniere' => ['resources/views/pages/blocks/_banniere_hero.blade.php', 'bg-cidst-secondary'],
]);
