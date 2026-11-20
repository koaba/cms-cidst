<?php

/*
 * Themes selectionnables dans admin/settings (colonne site_settings.theme).
 *
 * « cidst » est le theme par defaut : il n'a pas de palette et utilise les couleurs des
 * reglages. Un theme peut imposer une palette (cles : primary, secondary, accent, leurs
 * *_content, puis les roles bg, surface, text, muted, border ; optionnellement admin_bg, admin_nav,
 * admin_hover, admin_text pour la coque d'administration, et les etats danger, success, warning avec
 * leurs _soft et _content) ; les couleurs personnalisees
 * (site_settings.theme_colors) passent toujours apres la palette. Chaque couleur de texte
 * (_content) est choisie pour un contraste d'au moins 4,5.
 *
 * Prereglages (lot 3d-4c) : ils gardent l'ADN du logo (rouge vif, noir, fond clair) et changent la
 * temperature des neutres et l'accent. Chaque palette donne les 7 roles, l'accent et le texte pose sur
 * primaire, secondaire et accent (blanc ici, contraste >= 4,5, verifie par ThemePresetsTest).
 * Tout reste modifiable ensuite par les couleurs personnalisees.
 *
 * Clair et Sombre : lot 3e-2. Les themes de fetes arrivent au lot 3e-bis.
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

        // Lot 3e-2 : lumineux et neutre, rouge du logo sur blanc, accent bleu.
        'clair' => [
            'label' => 'Clair',
            'palette' => [
                'primary' => '#C41E2A',
                'primary_content' => '#FFFFFF',
                'secondary' => '#17181A',
                'secondary_content' => '#FFFFFF',
                'accent' => '#1D4ED8',
                'accent_content' => '#FFFFFF',
                'bg' => '#FAFAFB',
                'surface' => '#FFFFFF',
                'text' => '#111827',
                'muted' => '#4B5563',
                'border' => '#E5E7EB',
                'admin_bg' => '#F8FAFC',
                'admin_nav' => '#17181A',
                'admin_hover' => '#2A2C30',
                'admin_text' => '#D1D5DB',
            ],
        ],

        // Lot 3e-2 : fonds quasi noirs, rouge du logo eclairci (texte fonce pose dessus), accent turquoise.
        // Les couleurs d'etat sont toutes surchargees : les valeurs par defaut sont faites pour fond clair.
        'sombre' => [
            'label' => 'Sombre',
            'palette' => [
                'primary' => '#FF6B73',
                'primary_content' => '#1A0507',
                'secondary' => '#262B36',
                'secondary_content' => '#E8EAED',
                'accent' => '#5EEAD4',
                'accent_content' => '#06201C',
                'bg' => '#0F1115',
                'surface' => '#171A21',
                'text' => '#E8EAED',
                'muted' => '#A3AAB8',
                'border' => '#2E3440',
                'admin_bg' => '#0B0D11',
                'admin_nav' => '#12151A',
                'admin_hover' => '#252A33',
                'admin_text' => '#B6BDC9',
                'danger' => '#FCA5A5',
                'danger_soft' => '#3B0D0D',
                'danger_content' => '#FECACA',
                'success' => '#86EFAC',
                'success_soft' => '#0B2E1A',
                'success_content' => '#BBF7D0',
                'warning' => '#FCD34D',
                'warning_soft' => '#33230A',
                'warning_content' => '#FDE68A',
            ],
        ],
    ],
];
