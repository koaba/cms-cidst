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
    'variantes dark:' => ['/(?<![\w-])dark:/'],
]);
