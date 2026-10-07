<?php

function publicImgTags(): array
{
    $tags = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        $path = str_replace('\\', '/', $file->getPathname());

        if (! str_ends_with($path, '.blade.php') || str_contains($path, '/admin/')) {
            continue;
        }

        preg_match_all('/<img\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/s', file_get_contents($path), $matches);

        foreach ($matches[0] as $tag) {
            $tags[] = [basename($path), preg_replace('/\s+/', ' ', $tag)];
        }
    }

    return $tags;
}

it('impose loading ou fetchpriority sur chaque img publique', function () {
    $fautives = [];

    foreach (publicImgTags() as [$file, $tag]) {
        // Exemptions : lightbox (src vide, rempli en JS) et filigrane decoratif.
        if (str_contains($tag, 'id="lightbox-img"') || str_contains($tag, 'aria-hidden="true"')) {
            continue;
        }

        if (! preg_match('/\b(loading|fetchpriority)=/', $tag)) {
            $fautives[] = $file.' : '.$tag;
        }
    }

    expect($fautives)->toBe([]);
});

it('priorise le hero : fetchpriority high et jamais lazy', function () {
    $hero = collect(publicImgTags())->first(fn ($t) => $t[0] === '_banniere_hero.blade.php');

    expect($hero)->not->toBeNull()
        ->and($hero[1])->toContain('fetchpriority="high"')
        ->and($hero[1])->not->toContain('loading=');
});
