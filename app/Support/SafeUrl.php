<?php

namespace App\Support;

/**
 * Controle des liens saisis par les auteurs (boutons, sliders, bandeau).
 *
 * Sont acceptes : un chemin du site (/page, page, ?x=1), une ancre (#haut), http, https,
 * mailto et tel. Tout autre schema (javascript:, data:, vbscript:, file:, ftp:...) est refuse,
 * de meme qu'une adresse sans schema (//hote, \\hote) qui mene hors du site a l'insu de l'auteur.
 * Les caracteres de controle sont refuses : les navigateurs les retirent d'un lien,
 * ce qui permettrait de cacher un schema ("java<tab>script:").
 */
final class SafeUrl
{
    private const ALLOWED_SCHEMES = ['mailto', 'tel'];

    public static function isSafe(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        // //hote, \\hote, /\hote : les navigateurs lisent l'antislash comme un slash.
        if (preg_match('~^[\\\\/]{2}~', $url) === 1) {
            return false;
        }

        if (preg_match('~^([a-z][a-z0-9+.\-]*):~i', $url, $scheme) !== 1) {
            return true;
        }

        $name = strtolower($scheme[1]);

        if ($name === 'http' || $name === 'https') {
            return preg_match('~^https?://[^/?#\s]+~i', $url) === 1;
        }

        return in_array($name, self::ALLOWED_SCHEMES, true) && strlen($url) > strlen($scheme[0]);
    }

    /** Lien utilisable dans un attribut href : le lien lui-meme s'il est sain, sinon une ancre inoffensive. */
    public static function sanitize(?string $url): string
    {
        return self::isSafe($url) ? trim((string) $url) : '#';
    }
}
