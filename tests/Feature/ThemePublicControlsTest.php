<?php

// Lot 3d-5 : dans le site public, les champs de formulaire lisent les jetons du theme
// (fond surface, texte principal) et le texte pose sur la couleur primaire utilise le
// jeton calcule text-cidst-primary-content, jamais un text-white en dur.

function themeControlsRead(string $relative): string
{
    return (string) file_get_contents(base_path($relative));
}

/** @return list<string> Vues publiques (l'administration est traitee au lot 3f). */
function themeControlsPublicViews(): array
{
    return collect(File::allFiles(base_path('resources/views')))
        ->map(fn ($file) => str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname()))
        ->reject(fn (string $path) => str_contains($path, '/views/admin/') || str_contains($path, '/components/admin/'))
        ->values()
        ->all();
}

/** @return list<string> Balises input et textarea qui doivent porter les jetons du theme. */
function themeControlsFields(string $relative): array
{
    preg_match_all('/<(?:input|textarea)\b[^>]*>/s', themeControlsRead($relative), $matches);

    return collect($matches[0])
        ->reject(fn (string $tag) => str_contains($tag, 'name="website"') || str_contains($tag, 'type="file"'))
        ->values()
        ->all();
}

it('ne pose plus text-white sur un fond bg-cidst-primary dans les vues publiques', function () {
    $offenders = collect(themeControlsPublicViews())
        ->flatMap(fn (string $path) => collect(preg_split('/\R/', (string) file_get_contents($path)))
            ->filter(fn (string $line) => str_contains($line, 'bg-cidst-primary') && str_contains($line, 'text-white'))
            ->map(fn (string $line) => basename($path).' : '.trim($line)))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

it('utilise text-cidst-primary-content dans :name', function (string $view) {
    expect(themeControlsRead($view))->toContain('text-cidst-primary-content');
})->with([
    'le bandeau' => ['resources/views/components/news-ticker.blade.php'],
    'la page contact' => ['resources/views/public/contact.blade.php'],
    'la recherche' => ['resources/views/public/search/index.blade.php'],
    'les documents' => ['resources/views/public/pdf-documents/index.blade.php'],
    'la pagination' => ['resources/views/vendor/pagination/tailwind.blade.php'],
    'l accueil' => ['resources/views/welcome.blade.php'],
]);

it('donne le fond surface et le texte principal aux champs de :name', function (string $view, int $minimum) {
    $fields = themeControlsFields($view);

    expect(count($fields))->toBeGreaterThanOrEqual($minimum);

    foreach ($fields as $tag) {
        expect($tag)->toContain('bg-cidst-surface')->toContain('text-cidst-text');
    }
})->with([
    'la page contact' => ['resources/views/public/contact.blade.php', 5],
    'la recherche' => ['resources/views/public/search/index.blade.php', 1],
]);

it('n impose plus de rouge en dur au survol du bouton d accueil', function () {
    expect(themeControlsRead('resources/views/welcome.blade.php'))->not->toContain('hover:bg-red-700');
});
