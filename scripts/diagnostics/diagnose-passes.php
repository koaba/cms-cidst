<?php

/**
 * Diagnostic du mojibake multi-passes : applique successivement plusieurs
 * "réparations" (réinterprétation Latin-1 -> UTF-8) sur le contenu réel
 * d'un fichier, et affiche le résultat après chaque passe.
 *
 * Objectif : certains fichiers ont été corrompus plusieurs fois de suite
 * (rouverts/resauvés dans un mauvais encodage à plusieurs reprises). Une
 * seule passe de correction ne suffit pas pour eux. Ce script permet de
 * voir, passe par passe, à quel moment le texte redevient du français
 * lisible — sans jamais écrire sur le disque.
 *
 * Usage : php diagnose-passes.php <chemin_du_fichier> [nombre_max_de_passes]
 */

$path = $argv[1] ?? null;
$maxPasses = isset($argv[2]) ? (int) $argv[2] : 5;

if (! $path || ! file_exists($path)) {
    echo "Usage : php diagnose-passes.php <chemin_du_fichier> [nombre_max_de_passes]\n";
    exit(1);
}

$content = file_get_contents($path);

function onePass(string $text): ?string
{
    $result = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

    if ($result === false || ! mb_check_encoding($result, 'UTF-8')) {
        return null;
    }

    return $result;
}

function countMojibakeSignals(string $text): int
{
    // Compte les séquences qui trahissent encore du mojibake non résolu.
    preg_match_all('/Ã[\x{0080}-\x{00BF}]|Â[\x{0080}-\x{00BF}]|â‚¬|Æ\'/u', $text, $m);

    return count($m[0]);
}

// Récupère les lignes contenant des caractères suspects, pour un aperçu ciblé
// plutôt que d'afficher tout le fichier à chaque passe.
$lines = preg_split('/\r\n|\r|\n/', $content);
$suspectLineNumbers = [];

foreach ($lines as $i => $line) {
    if (preg_match('/Ã|Â|Æ/u', $line)) {
        $suspectLineNumbers[] = $i;
    }
}

echo "=== Diagnostic multi-passes : {$path} ===\n";
echo 'Lignes suspectes repérées : ' . count($suspectLineNumbers) . "\n\n";

$current = $content;

echo "--- PASSE 0 (état actuel du fichier) ---\n";
echo 'Signaux mojibake restants : ' . countMojibakeSignals($current) . "\n";
foreach (array_slice($suspectLineNumbers, 0, 10) as $lineNo) {
    $currentLines = preg_split('/\r\n|\r|\n/', $current);
    echo "  L" . ($lineNo + 1) . ": " . trim($currentLines[$lineNo]) . "\n";
}
echo "\n";

for ($pass = 1; $pass <= $maxPasses; $pass++) {
    $next = onePass($current);

    if ($next === null) {
        echo "--- PASSE {$pass} : échec de conversion, arrêt ---\n";
        break;
    }

    $current = $next;
    $signals = countMojibakeSignals($current);

    echo "--- PASSE {$pass} ---\n";
    echo "Signaux mojibake restants : {$signals}\n";

    $currentLines = preg_split('/\r\n|\r|\n/', $current);
    foreach (array_slice($suspectLineNumbers, 0, 10) as $lineNo) {
        echo "  L" . ($lineNo + 1) . ": " . trim($currentLines[$lineNo] ?? '') . "\n";
    }
    echo "\n";

    if ($signals === 0) {
        echo ">>> Signaux mojibake épuisés après {$pass} passe(s). Probable bon compte. <<<\n\n";
        break;
    }
}

echo "=== Fin du diagnostic — aucun fichier n'a été modifié ===\n";
