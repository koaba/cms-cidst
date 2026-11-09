<?php

use Illuminate\Support\Facades\File;

// Garde : chaque vue admin doit definir le <title> via le composant layout.
it('donne un titre a chaque vue admin', function () {
    $sansTitre = collect(File::allFiles(resource_path('views/admin')))
        ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'))
        ->filter(function ($f) {
            $src = file_get_contents($f->getPathname());

            return str_contains($src, '<x-admin.layout')
                && ! preg_match('/<x-admin\.layout\s+:?title=/', $src);
        })
        ->map(fn ($f) => str_replace('\\', '/', $f->getRelativePathname()))
        ->sort()
        ->values()
        ->all();

    expect($sansTitre)->toBe([]);
});
