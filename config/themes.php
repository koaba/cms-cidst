<?php

/*
 * Themes selectionnables dans admin/settings (colonne site_settings.theme).
 *
 * « cidst » est le theme par defaut : il n'a pas de palette et utilise les couleurs des
 * reglages. Un theme peut imposer une palette (cles : primary, secondary, accent, leurs
 * *_content, puis les roles bg, surface, text, muted, border) ; les couleurs personnalisees
 * (site_settings.theme_colors) passent toujours apres la palette. Chaque couleur de texte
 * (_content) est choisie pour un contraste d'au moins 4,5.
 *
 * Les themes Clair, Sombre et de fetes arrivent aux lots 3e et 3e-bis.
 */
return [
    'default' => 'cidst',

    'themes' => [
        'cidst' => [
            'label' => 'CIDST (par défaut)',
        ],
    ],
];
