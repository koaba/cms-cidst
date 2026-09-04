<?php

/**
 * Applique N passes de correction mojibake (Latin-1 -> UTF-8) sur un fichier,
 * avec sauvegarde automatique en .bak avant toute écriture.
 *
 * Usage : php apply-mojibake-fix.php <chemin_du_fichier> <nombre_de_passes>
 */

$path = $argv[1] ?? null;
$passes = isset($argv[2]) ? (int) $argv[2] : null;

if (! $path || ! file_exists($path) || $passes === null) {
    echo "Usage : php apply-mojibake-fix.php <chemin_du_fichier> <nombre_de_passes>\n";
    exit(1);
}

function onePass(string $text): ?string
{
    $result = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

    if ($result === false || ! mb_check_encoding($result, 'UTF-8')) {
        return null;
    }

    return $result;
}

$original = file_get_contents($path);
$current = $original;

for ($i = 1; $i <= $passes; $i++) {
    $next = onePass($current);

    if ($next === null) {
        echo "Échec à la passe {$i} — aucune modification appliquée.\n";
        exit(1);
    }

    $current = $next;
}

$backupPath = $path . '.bak';
file_put_contents($backupPath, $original);
file_put_contents($path, $current);

echo "OK — {$passes} passe(s) appliquée(s) sur {$path}\n";
echo "Sauvegarde de l'original : {$backupPath}\n";
