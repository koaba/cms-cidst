<?php

/**
 * Variante ligne par ligne de diagnose-passes.php : plus robuste quand un
 * seul caractère isolé ailleurs dans le fichier fait échouer la conversion
 * de l'ensemble du contenu en une fois. Chaque ligne est traitée
 * indépendamment, jusqu'à N passes ou jusqu'à ce que le nombre de signaux
 * mojibake retombe à 0 pour cette ligne.
 *
 * Usage : php diagnose-passes-per-line.php <chemin_du_fichier> [nombre_max_de_passes]
 */

$path = $argv[1] ?? null;
$maxPasses = isset($argv[2]) ? (int) $argv[2] : 6;

if (! $path || ! file_exists($path)) {
    echo "Usage : php diagnose-passes-per-line.php <chemin_du_fichier> [nombre_max_de_passes]\n";
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

function countMojibakeSignals(string $text): int
{
    preg_match_all('/Ã[\x{0080}-\x{00BF}]|Â[\x{0080}-\x{00BF}]|â‚¬|Æ\'/u', $text, $m);

    return count($m[0]);
}

$content = file_get_contents($path);
$lines = preg_split('/\r\n|\r|\n/', $content);

echo "=== Diagnostic ligne par ligne : {$path} ===\n\n";

foreach ($lines as $i => $line) {
    if (countMojibakeSignals($line) === 0) {
        continue;
    }

    echo "--- Ligne " . ($i + 1) . " ---\n";
    echo "  Passe 0 : " . trim($line) . "\n";

    $current = $line;
    $resolved = false;

    for ($pass = 1; $pass <= $maxPasses; $pass++) {
        $next = onePass($current);

        if ($next === null) {
            echo "  Passe {$pass} : échec de conversion pour cette ligne (caractère hors Windows-1252 ?)\n";
            break;
        }

        $current = $next;
        $signals = countMojibakeSignals($current);
        echo "  Passe {$pass} : " . trim($current) . "  [signaux restants: {$signals}]\n";

        if ($signals === 0) {
            $resolved = true;
            break;
        }
    }

    if ($resolved) {
        echo "  >>> Résolu en {$pass} passe(s) <<<\n";
    } else {
        echo "  >>> NON résolu automatiquement — nécessite une correction manuelle <<<\n";
    }

    echo "\n";
}

echo "=== Fin du diagnostic — aucun fichier n'a été modifié ===\n";
