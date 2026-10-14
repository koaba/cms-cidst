<?php

use Symfony\Component\Finder\Finder;

/**
 * Nombre exact de balises <img brutes autorisees par vue publique.
 * Toute autre image doit passer par <x-media-img>.
 * Le test echoue si un compte augmente (nouvel <img brut) ou diminue
 * (une exception a ete migree : mettre cette liste a jour).
 */
function expectedRawImgCounts(): array
{
    return [
        // Le composant lui-meme.
        'components/media-img.blade.php' => 1,
        // Logos du menu, hors table media, loading="eager" voulu.
        'components/main-menu.blade.php' => 2,
        // Logo du filigrane, decoratif (alt vide, aria-hidden).
        'components/watermark-overlay.blade.php' => 1,
        // Repli des sliders sans media (teste dans PublicSlidersMediaImgTest).
        'welcome.blade.php' => 1,
        'public/sliders/index.blade.php' => 1,
        // Image d'article stockee hors table media (migration a prevoir).
        'components/news-sidebar.blade.php' => 1,
        'public/articles/index.blade.php' => 1,
        // show : image d'article (13), miniature PDF (66),
        // miniature YouTube externe (146), lightbox rempli en JavaScript (173).
        'public/articles/show.blade.php' => 4,
        // Miniatures de PDF deja reduites, taille fixe.
        'pages/blocks/_pdf.blade.php' => 1,
        'public/pdf-documents/index.blade.php' => 1,
        'public/pdf-documents/show.blade.php' => 1,
    ];
}

function actualRawImgCounts(): array
{
    $base = str_replace('\\', '/', resource_path('views')).'/';
    $counts = [];

    foreach ((new Finder)->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
        $relative = str_replace($base, '', str_replace('\\', '/', $file->getPathname()));

        if (str_starts_with($relative, 'admin/') || str_contains($relative, '/admin/')) {
            continue;
        }

        $count = preg_match_all('/<img\b/', $file->getContents());

        if ($count > 0) {
            $counts[$relative] = $count;
        }
    }

    ksort($counts);

    return $counts;
}

it('limite les img bruts des vues publiques aux exceptions documentees', function () {
    $expected = expectedRawImgCounts();
    ksort($expected);

    expect(actualRawImgCounts())->toBe($expected);
});
