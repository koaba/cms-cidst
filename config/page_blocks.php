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
        'accordeon' => 'Accordéon',
        // Types restants à implémenter :
        // 'banniere_hero', 'chiffres_cles'
    ],

    /*
     * Types internes : jamais proposés dans le menu "Ajouter un bloc" à la
     * racine d'une page (contrairement à `types`), mais valides pour la
     * validation et le rendu. Créés uniquement de façon implicite par un
     * bloc parent (ex. accordeon_item est créé par le bouton "+ Ajouter un
     * item" du bloc accordeon, jamais choisi directement par l'utilisateur).
     */
    'internal_types' => [
        'accordeon_item' => 'Item d\'accordéon',
    ],

    /*
     * Types autorisés à l'intérieur d'une colonne du bloc `colonnes`.
     * Ajouter un type ici suffit à le rendre disponible dans les colonnes,
     * tant qu'il a déjà son `case` dans PageBlockController::validateForType().
     * `colonnes` ne doit jamais y figurer (pas d'imbrication de colonnes
     * dans des colonnes). `diaporama`/`slider`, une fois implémentés,
     * resteront volontairement exclus (décision produit : bloc pleine largeur).
     * `accordeon` volontairement exclu pour l'instant : pas encore testé en
     * imbrication (colonnes > accordeon > accordeon_item > contenu serait
     * une récursion à 4 niveaux) — à activer plus tard si besoin, une ligne
     * suffira. `accordeon_item` n'y figurera jamais (type interne uniquement).
     */
    'nestable_in_columns' => [
        'texte', 'separateur', 'citation', 'bouton',
        'image', 'video', 'section_fond', 'galerie', 'pdf',
    ],
];