<?php

/*
 * Themes selectionnables dans admin/settings (colonne site_settings.theme).
 *
 * « cidst » est le theme par defaut : il n'a pas de palette et utilise les couleurs
 * saisies dans les reglages. Un autre theme impose sa palette (les couleurs des
 * reglages ne s'appliquent alors plus). Chaque role a sa couleur de texte (_content),
 * choisie pour un contraste d'au moins 4,5 (verifie par ThemeSelectionTest).
 */
return [
    'default' => 'cidst',

    'themes' => [
        'cidst' => [
            'label' => 'CIDST (par défaut)',
        ],

        'itadori' => [
            'label' => 'Itadori',
            'palette' => [
                'primary' => '#E61919',
                'primary_content' => '#FFFFFF',
                'secondary' => '#24335C',
                'secondary_content' => '#FFFFFF',
                'accent' => '#FFAFBD',
                'accent_content' => '#5C111C',
            ],
        ],
    ],
];
