<?php

/*
| Titres et descriptions SEO des pages publiques qui ne sont pas portees par
| un modele (accueil, listes, contact, recherche). Les modeles Article et Page
| gardent leur SEO propre (table seo_metas, via SeoService).
|
| Jetons remplaces par SeoService::forPage() : :site (nom du site) et, pour les
| pages de categorie, :name. Description : 160 caracteres maximum.
| "noindex" => true retire la page des moteurs de recherche.
*/

return [
    // Code pays ISO 3166-1 (alpha-2) de l'adresse postale du JSON-LD de l'accueil.
    'organization' => [
        'country' => 'MG',
    ],

    'breadcrumbs' => [
        'home' => 'Accueil',
        'blog' => 'Blog',
    ],

    'pages' => [
        'home' => [
            'title' => "CIDST - Centre d'Information et de Documentation Scientifique et Technique",
            'description' => 'Le CIDST réunit actualités, publications et documents de référence en information scientifique et technique.',
        ],
        'blog' => [
            'title' => 'Blog — :site',
            'description' => 'Les dernières actualités et publications du CIDST.',
        ],
        'blog_category' => [
            'title' => 'Articles : :name — :site',
            'description' => 'Les articles du CIDST classés dans la catégorie :name.',
        ],
        'pages' => [
            'title' => 'Pages — :site',
            'description' => "Toutes les pages d'information du CIDST.",
        ],
        'documents' => [
            'title' => 'Documents — :site',
            'description' => 'Les documents PDF du CIDST, à consulter et à télécharger, classés par catégorie.',
        ],
        'documents_category' => [
            'title' => 'Documents : :name — :site',
            'description' => 'Les documents PDF du CIDST de la catégorie :name.',
        ],
        'sliders' => [
            'title' => 'À la une — :site',
            'description' => 'Les mises en avant du CIDST.',
        ],
        'contact' => [
            'title' => 'Contact — :site',
            'description' => "Une question ou une demande d'information ? Écrivez au CIDST via le formulaire de contact.",
        ],
        'search' => [
            'title' => 'Recherche — :site',
            'description' => 'Recherchez dans les articles et les pages du CIDST.',
            'noindex' => true,
        ],
    ],
];
