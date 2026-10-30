<?php

/**
 * MySQL (utf8mb4) refuse un octet Latin-1 isole que SQLite accepte sans rien dire :
 * un test enregistre en ANSI passe en local sur SQLite et casse sur MySQL.
 */
it('garde tous les fichiers sources en UTF-8 valide', function () {
    $roots = ['app', 'bootstrap', 'config', 'database', 'lang', 'resources', 'routes', 'tests'];
    $extensions = ['php', 'js', 'css', 'json', 'md'];

    // Exception connue et documentee : lu et ecrit en Latin-1.
    $latin1 = [base_path('config/display.php')];

    $invalid = [];

    foreach ($roots as $root) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($root), FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! in_array($file->getExtension(), $extensions, true) || in_array($file->getPathname(), $latin1, true)) {
                continue;
            }

            if (! mb_check_encoding(file_get_contents($file->getPathname()), 'UTF-8')) {
                $invalid[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }
    }

    expect($invalid)->toBe([]);
});
