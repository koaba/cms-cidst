<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy
    |--------------------------------------------------------------------------
    |
    | mode : off | report-only | enforce
    |   report-only : le navigateur signale les violations dans la console sans
    |                 rien bloquer (valeur par defaut, deploiement sans risque).
    |   enforce     : le navigateur bloque ce qui n'est pas autorise.
    |
    | Dette connue : script-src garde 'unsafe-inline' (scripts et onclick inline
    | dans une quarantaine de vues) et 'unsafe-eval' (build standard d'Alpine).
    | Les retirer demande des nonces, la suppression des onclick et @alpinejs/csp.
    |
    | frame-src doit rester aligne sur les hotes produits par App\Support\VideoEmbed
    | (un test le verifie), plus OpenStreetMap pour la carte de la page Contact
    | (chargee au clic). Si les fichiers passent sur un CDN ou un stockage
    | externe, ajouter son hote a img-src et media-src.
    |
    */

    'csp' => [
        'mode' => env('CSP_MODE', 'report-only'),

        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://cdnjs.cloudflare.com'],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'],
            'font-src' => ["'self'", 'data:', 'https://fonts.bunny.net'],
            'img-src' => ["'self'", 'data:', 'blob:', 'https://img.youtube.com'],
            'media-src' => ["'self'", 'blob:'],
            'connect-src' => ["'self'"],
            'worker-src' => ["'self'", 'blob:', 'https://cdnjs.cloudflare.com'],
            'frame-src' => ['https://www.youtube.com', 'https://player.vimeo.com', 'https://www.openstreetmap.org'],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ],
    ],

];
