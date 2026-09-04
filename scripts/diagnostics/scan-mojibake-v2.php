<?php

/**
 * Scan (lecture seule) des fichiers du projet à la recherche de VRAI mojibake
 * — texte UTF-8 ayant subi un double encodage.
 *
 * Version corrigée : la v1 de ce script confondait à tort les caractères
 * accentués français normaux (é, à, è...) avec du mojibake, à cause d'un
 * regex trop large. Cette version ne cible que les séquences qui sont
 * réellement des artefacts de double encodage :
 *   - "Ã" suivi d'un caractère de continuation UTF-8 mal interprété
 *     (Â, ©, ¨, ª, «, etc. — la plage \x{0080}-\x{00BF} qui n'apparaît
 *     JAMAIS seule dans du français valide)
 *   - séquences composées type "Ã¢â‚¬" (apostrophes/guillemets typographiques
 *     mal encodés), "Â " (espace insécable mal encodé), etc.
 *
 * Ne modifie AUCUN fichier.
 *
 * Usage : php scan-mojibake.php
 */

$directories = [
    'resources/views',
    'app',
    'database',
];

$extensions = ['php', 'blade.php'];

/**
 * Motif ciblant UNIQUEMENT les séquences de double-encodage UTF-8→Latin1→UTF-8.
 * Contrairement à la v1, on exige la présence du caractère "Ã" (U+00C3) ou "Â"
 * (U+00C2) suivi d'un caractère de la plage de continuation UTF-8 (0080-00BF),
 * qui ne forme jamais un digramme français valide. Un "é" ou "à" isolé ne
 * matche donc plus.
 */
const MOJIBAKE_PATTERN = '/(?:Ã[\x{0080}-\x{00BF}\x{0090}]|Â[\x{0080}-\x{00BF}]|â\x{0080}[\x{0090}-\x{009F}]|Æ\x{2019}?)[A-Za-zÀ-ÿ]*/u';

function tryFixMojibake(string $text): ?string
{
    $latin1Bytes = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

    if ($latin1Bytes === false) {
        return null;
    }

    if (! mb_check_encoding($latin1Bytes, 'UTF-8')) {
        return null;
    }

    // Si le résultat contient encore le motif mojibake, on ne le considère
    // pas comme réparé en un seul passage (rare, cas de double mojibake).
    if (preg_match(MOJIBAKE_PATTERN, $latin1Bytes)) {
        return null;
    }

    return $latin1Bytes;
}

function scanContent(string $content): array
{
    preg_match_all(MOJIBAKE_PATTERN, $content, $matches);

    return array_values(array_unique($matches[0]));
}

function collectFiles(array $directories, array $extensions): array
{
    $files = [];

    foreach ($directories as $dir) {
        if (! is_dir($dir)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            foreach ($extensions as $ext) {
                if (str_ends_with($path, '.' . $ext)) {
                    $files[] = $path;
                    break;
                }
            }
        }
    }

    return $files;
}

// ---------------------------------------------------------------------

$files = collectFiles($directories, $extensions);
$report = [];
$totalOccurrences = 0;

foreach ($files as $path) {
    $content = @file_get_contents($path);

    if ($content === false) {
        continue;
    }

    $suspects = scanContent($content);

    if (empty($suspects)) {
        continue;
    }

    $examples = [];

    foreach (array_slice($suspects, 0, 8) as $suspect) {
        $fixed = tryFixMojibake($suspect);
        $examples[] = [
            'original' => $suspect,
            'fixed' => $fixed ?? '(non réparable automatiquement — à vérifier manuellement)',
        ];
    }

    $report[$path] = [
        'count' => count($suspects),
        'examples' => $examples,
    ];

    $totalOccurrences += count($suspects);
}

// ---------------------------------------------------------------------

echo "=== Rapport de scan mojibake (v2 - motif resserre) ===\n\n";
echo 'Fichiers scannés : ' . count($files) . "\n";
echo 'Fichiers concernés : ' . count($report) . "\n";
echo 'Séquences suspectes distinctes (total) : ' . $totalOccurrences . "\n\n";

if (empty($report)) {
    echo "Aucun mojibake détecté avec le motif utilisé.\n";
    exit(0);
}

echo "--- Détail par fichier ---\n\n";

foreach ($report as $path => $info) {
    echo "[{$info['count']} séquence(s)] {$path}\n";

    foreach ($info['examples'] as $example) {
        echo "    \"{$example['original']}\"  →  \"{$example['fixed']}\"\n";
    }

    echo "\n";
}

echo "=== Fin du rapport — aucun fichier n'a été modifié ===\n";
