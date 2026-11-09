<?php

use Illuminate\Support\Facades\File;

it('n utilise plus de gris Tailwind en dur dans les vues, le JS et les configs', function () {
    $files = collect([base_path('tailwind.config.js'), base_path('tailwind.public.config.js')])
        ->merge(collect(File::allFiles(resource_path('views')))->map(fn ($f) => $f->getPathname()))
        ->merge(collect(File::allFiles(resource_path('js')))->map(fn ($f) => $f->getPathname()));

    $offenders = $files
        ->filter(fn ($path) => preg_match('/\b(?:text|bg|border|ring|divide|placeholder|from|to|via|fill|stroke)-gray-\d{2,3}\b/', (string) file_get_contents($path)) === 1)
        ->map(fn ($path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
