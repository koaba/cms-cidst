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
 *  - decor  : cle du jeu d'icones dans 'decors' ci-dessous (lot 3e-bis-3).
 *
 * L'ordre compte : si deux fenetres se chevauchent, la premiere du tableau l'emporte
 * (FestivalCalendarTest garantit qu'aucun chevauchement n'existe de 2026 a 2060).
 */
return [
    // Interrupteur general : FESTIVALS_ENABLED=false coupe themes ET decors (.env).
    'enabled' => env('FESTIVALS_ENABLED', true),

    'festivals' => [
        'noel' => [
            'label' => 'Noël',
            'rule' => ['fixed' => [12, 25]],
            'before' => 10,
            'after' => 1,
            'theme' => 'fete-noel',
            'decor' => 'noel',
        ],
        // Fin d'annee : prend le relais de Noel (27 decembre) jusqu'au 2 janvier. Un seul theme pour les deux.
        'nouvelan' => [
            'label' => 'Fin d\'année et Nouvel An',
            'rule' => ['fixed' => [1, 1]],
            'before' => 5,
            'after' => 1,
            'theme' => 'fete-nouvel-an',
            'decor' => 'nouvelan',
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

    /*
     * Decors (lot 3e-bis-3) : icones SVG de resources/views/partials/festival/icons/<nom>.blade.php.
     *  - logo  : icone posee sur le coin du logo du menu.
     *  - strip : icones du bandeau au-dessus du menu (les 4 premieres restent visibles sur mobile).
     */
    'decors' => [
        'noel' => ['logo' => 'hat', 'strip' => ['hat', 'gift', 'gift', 'hat', 'gift', 'gift', 'hat']],
        'nouvelan' => ['logo' => 'star', 'strip' => ['firework', 'star', 'glass', 'firework', 'star', 'glass', 'firework']],
        'paques' => ['logo' => 'egg', 'strip' => ['egg', 'egg', 'egg', 'egg', 'egg', 'egg', 'egg']],
        'pentecote' => ['logo' => 'flame', 'strip' => ['flame', 'dove', 'flame', 'dove', 'flame', 'dove', 'flame']],
        'independance' => ['logo' => 'flag', 'strip' => ['flag', 'flag', 'flag', 'flag', 'flag', 'flag', 'flag']],
    ],
];
