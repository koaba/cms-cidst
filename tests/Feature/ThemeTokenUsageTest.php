<?php

// Lot 3d-2 : les vues lisent les roles de couleur (surface, texte principal) au lieu de
// couleurs figees. Dans l'esprit de NoHardcodedGrayTest : ces tests lisent les sources.

function themeUsageFiles(string ...$directories): array
{
    return collect($directories)
        ->flatMap(fn (string $directory) => File::allFiles(base_path($directory)))
        ->map(fn ($file) => $file->getPathname())
        ->all();
}

function themeUsageOffenders(array $paths, string $pattern): array
{
    return collect($paths)
        ->filter(fn (string $path) => preg_match($pattern, (string) file_get_contents($path)) === 1)
        ->map(fn (string $path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();
}

function themeUsageSource(string $relative): string
{
    return (string) file_get_contents(base_path($relative));
}

it('n utilise aucun bg-white dans l administration (surface = bg-cidst-surface)', function () {
    $paths = themeUsageFiles('resources/views/admin', 'resources/views/components/admin');

    expect(themeUsageOffenders($paths, '/(?<![\w-])bg-white(?![\w-])/'))->toBe([]);
});

it('utilise la surface du theme sur les fonds blancs de l administration', function () {
    $count = collect(themeUsageFiles('resources/views/admin', 'resources/views/components/admin'))
        ->sum(fn (string $path) => preg_match_all('/(?<![\w-])bg-cidst-surface(?![\w-])/', (string) file_get_contents($path)));

    expect($count)->toBeGreaterThanOrEqual(22);
});

it('n utilise plus text-cidst-ink : le texte principal a son propre jeton', function () {
    $paths = themeUsageFiles('resources/views', 'resources/js');

    expect(themeUsageOffenders($paths, '/(?<![\w-])text-cidst-ink(?![\w-])/'))->toBe([]);
});

it('garde le texte principal dans le menu et la page article', function (string $view) {
    expect(themeUsageSource($view))->toContain('text-cidst-text');
})->with([
    'resources/views/components/main-menu.blade.php',
    'resources/views/public/articles/show.blade.php',
]);

it('garde un texte sombre fixe sur le bouton blanc de la section de fond', function () {
    expect(themeUsageSource('resources/views/pages/blocks/_section_fond.blade.php'))
        ->toContain('bg-white text-cidst-secondary')
        ->and(themeUsageSource('resources/views/pages/blocks/_section_fond.blade.php'))
        ->toContain("'gray' => 'bg-cidst-bg text-cidst-text'");
});

it('garde les fonds sombres fixes sur le jeton secondaire partage', function () {
    expect(themeUsageSource('resources/views/pages/blocks/_banniere_hero.blade.php'))->toContain('bg-cidst-secondary')
        ->and(themeUsageSource('resources/views/pages/blocks/_section_fond.blade.php'))->toContain("'dark' => 'bg-cidst-secondary text-white'");
});
