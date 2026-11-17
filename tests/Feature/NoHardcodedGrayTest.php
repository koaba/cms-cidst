<?php

function scannedStyleFiles(): array
{
    return collect([base_path('tailwind.config.js'), base_path('tailwind.public.config.js')])
        ->merge(collect(File::allFiles(resource_path('views')))->map(fn ($f) => $f->getPathname()))
        ->merge(collect(File::allFiles(resource_path('js')))->map(fn ($f) => $f->getPathname()))
        ->all();
}

it('ne contient aucun style en dur interdit', function (string $pattern) {
    $offenders = collect(scannedStyleFiles())
        ->filter(fn ($path) => preg_match($pattern, (string) file_get_contents($path)) === 1)
        ->map(fn ($path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
})->with([
    'gris Tailwind' => ['/\b(?:text|bg|border|ring|divide|placeholder|from|to|via|fill|stroke)-gray-\d{2,3}\b/'],
    'indigo de Breeze' => ['/\b(?:text|bg|border|ring|divide|placeholder|from|to|via|fill|stroke)-indigo-\d{2,3}\b/'],
    'rouge Tailwind (utiliser cidst-danger)' => ['/\b(?:text|bg|border|ring|divide|placeholder|from|to|via|fill|stroke)-red-\d{2,3}\b/'],
    'vert Tailwind (utiliser cidst-success)' => ['/\b(?:text|bg|border|ring|divide|placeholder|from|to|via|fill|stroke)-(?:green|emerald)-\d{2,3}\b/'],
    'orange, jaune, ambre Tailwind (utiliser cidst-warning)' => ['/\b(?:text|bg|border|ring|divide|placeholder|from|to|via|fill|stroke)-(?:orange|yellow|amber)-\d{2,3}\b/'],
    'variantes dark:' => ['/(?<![\w-])dark:/'],
]);

it('ne contient aucun bleu Tailwind dans les vues publiques', function () {
    $adminOnly = fn (string $path) => str_contains($path, DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR.'profile'.DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR.'auth'.DIRECTORY_SEPARATOR);

    $offenders = collect(File::allFiles(resource_path('views')))
        ->map(fn ($f) => $f->getPathname())
        ->reject($adminOnly)
        ->filter(fn ($path) => preg_match('/\b(?:hover:|focus:)?(?:text|bg|border|ring)-blue-\d{2,3}\b/', (string) file_get_contents($path)) === 1)
        ->map(fn ($path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
