<?php

return [
    'types' => [
        'texte' => 'Texte',
        'separateur' => 'Séparateur',
        'citation' => 'Citation',
        'bouton' => 'Bouton',
        'image' => 'Image',
        'video' => 'Vidéo',
        'section_fond' => 'Section à fond coloré',
        'galerie' => 'Galerie',
        'pdf' => 'Document PDF',
        'colonnes' => 'Colonnes',
        // Types restants à implémenter :
        // 'accordeon', 'banniere_hero', 'chiffres_cles'
    ],

    /*
     * Types autorisés à l'intérieur d'une colonne du bloc `colonnes`.
     * Ajouter un type ici suffit à le rendre disponible dans les colonnes,
     * tant qu'il a déjà son `case` dans PageBlockController::validateForType().
     * `colonnes` ne doit jamais y figurer (pas d'imbrication de colonnes
     * dans des colonnes). `diaporama`/`slider`, une fois implémentés,
     * resteront volontairement exclus (décision produit : bloc pleine largeur).
     */
    'nestable_in_columns' => [
        'texte', 'separateur', 'citation', 'bouton',
        'image', 'video', 'section_fond', 'galerie', 'pdf',
    ],
];