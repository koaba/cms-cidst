<?php

/*
 * Fetes automatiques (lot 3e-bis-1) : une fete = une fenetre de dates, calculee chaque annee.
 *
 * Cles :
 *  - label  : nom affiche dans les Reglages et le guide.
 *  - rule   : ['fixed' => [mois, jour]] pour une date fixe, ['easter' => decalage en jours]
 *             pour une fete mobile (Paques = 0, Pentecote = 49).
 *  - before / after : jours de decoration avant et apres le jour de la fete.
 *  - theme  : cle du theme de fete dans config/themes.php (ajoute au lot 3e-bis-2).
 *  - decor  : jeu d'icones SVG (lot 3e-bis-3).
 *
 * L'ordre compte : si deux fenetres se chevauchent, la premiere du tableau l'emporte
 * (FestivalCalendarTest garantit qu'aucun chevauchement n'existe de 2026 a 2060).
 */
return [
    'festivals' => [
        'noel' => [
            'label' => 'Noël',
            'rule' => ['fixed' => [12, 25]],
            'before' => 10,
            'after' => 1,
            'theme' => 'fete-noel',
            'decor' => 'noel',
        ],
        'paques' => [
            'label' => 'Pâques',
            'rule' => ['easter' => 0],
            'before' => 7,
            'after' => 1,
            'theme' => 'fete-paques',
            'decor' => 'paques',
        ],
        'pentecote' => [
            'label' => 'Pentecôte',
            'rule' => ['easter' => 49],
            'before' => 3,
            'after' => 1,
            'theme' => 'fete-pentecote',
            'decor' => 'pentecote',
        ],
        'independance' => [
            'label' => 'Indépendance',
            'rule' => ['fixed' => [6, 26]],
            'before' => 7,
            'after' => 1,
            'theme' => 'fete-independance',
            'decor' => 'independance',
        ],
    ],
];
