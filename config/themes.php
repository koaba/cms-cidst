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
 * Prereglages (lot 3d-4c) : ils gardent l'ADN du logo (rouge vif, noir, fond clair) et changent la
 * temperature des neutres et l'accent. Chaque palette donne les 7 roles, l'accent et le texte pose sur
 * primaire, secondaire et accent (blanc ici, contraste >= 4,5, verifie par ThemePresetsTest).
 * Tout reste modifiable ensuite par les couleurs personnalisees.
 *
 * Les themes Clair, Sombre et de fetes arrivent aux lots 3e et 3e-bis.
 */
return [
    'default' => 'cidst',

    'themes' => [
        'cidst' => [
            'label' => 'CIDST (par défaut)',
        ],

        // Académique : bordeaux profond, accent or bruni, fonds légèrement rosés.
        'bordeaux' => [
            'label' => 'Bordeaux',
            'palette' => [
                'primary' => '#9E1B32',
                'primary_content' => '#FFFFFF',
                'secondary' => '#2A1519',
                'secondary_content' => '#FFFFFF',
                'accent' => '#8A6A1F',
                'accent_content' => '#FFFFFF',
                'bg' => '#F7F1F1',
                'surface' => '#FFFFFF',
                'text' => '#241518',
                'muted' => '#5A4A4D',
                'border' => '#E0D3D5',
            ],
        ],

        // Sobre : rouge du logo sur neutres froids, accent sarcelle.
        'ardoise' => [
            'label' => 'Ardoise',
            'palette' => [
                'primary' => '#C41E2A',
                'primary_content' => '#FFFFFF',
                'secondary' => '#1E293B',
                'secondary_content' => '#FFFFFF',
                'accent' => '#0F766E',
                'accent_content' => '#FFFFFF',
                'bg' => '#EEF2F6',
                'surface' => '#FFFFFF',
                'text' => '#1B2433',
                'muted' => '#475569',
                'border' => '#D5DCE5',
            ],
        ],

        // Chaleureux : rouge franc sur neutres crème, accent ocre.
        'sable' => [
            'label' => 'Sable',
            'palette' => [
                'primary' => '#B91C28',
                'primary_content' => '#FFFFFF',
                'secondary' => '#2B2118',
                'secondary_content' => '#FFFFFF',
                'accent' => '#8A5A12',
                'accent_content' => '#FFFFFF',
                'bg' => '#F6F0E6',
                'surface' => '#FFFDF8',
                'text' => '#2B2118',
                'muted' => '#5C5044',
                'border' => '#E2D8C8',
            ],
        ],
    ],
];
