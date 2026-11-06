<?php

use App\Enums\UserRole;

return [
    ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'description' => "Statistiques, activit\u{e9} r\u{e9}cente et \u{e9}l\u{e9}ments \u{e0} traiter.", 'active' => 'admin.dashboard'],
    ['label' => 'Articles', 'route' => 'admin.articles.index', 'description' => "R\u{e9}diger, publier et programmer les articles du blog.", 'active' => 'admin.articles.*'],
    ['label' => 'Pages', 'route' => 'admin.pages.index', 'description' => "Cr\u{e9}er et organiser les pages du site par blocs.", 'active' => 'admin.pages.*'],
    ['label' => 'Catégories', 'route' => 'admin.categories.index', 'description' => "Classer les articles par th\u{e8}me.", 'active' => 'admin.categories.*', 'roles' => [UserRole::SuperAdmin->value]],
    ['label' => 'Médiathèque', 'route' => 'admin.media.index', 'description' => "Biblioth\u{e8}que des images, PDF et vid\u{e9}os du site.", 'active' => 'admin.media.*'],
    ['label' => 'Sliders', 'route' => 'admin.sliders.index', 'description' => "Diaporamas affich\u{e9}s sur les pages publiques.", 'active' => 'admin.sliders.*'],
    ['label' => 'Documents PDF', 'route' => 'admin.pdf-documents.index', 'description' => "Documents PDF t\u{e9}l\u{e9}chargeables par les visiteurs.", 'active' => 'admin.pdf-documents.*', 'roles' => [UserRole::SuperAdmin->value]],
    ['label' => 'Catégories PDF', 'route' => 'admin.pdf-categories.index', 'description' => 'Classement des documents PDF par rubrique.', 'active' => 'admin.pdf-categories.*', 'roles' => [UserRole::SuperAdmin->value]],
    ['label' => 'News Ticker', 'route' => 'admin.news-tickers.index', 'description' => "Bandeau d\u{e9}filant des actualit\u{e9}s en haut du site.", 'active' => 'admin.news-tickers.*'],
    ['label' => 'Menus', 'route' => 'admin.menus.index', 'description' => 'Navigation principale du site public.', 'active' => 'admin.menus.*', 'roles' => [UserRole::SuperAdmin->value]],
    ['label' => 'Réglages', 'route' => 'admin.settings.edit', 'description' => "Identit\u{e9}, couleurs et code de suppression.", 'active' => 'admin.settings.*', 'roles' => [UserRole::SuperAdmin->value]],
    ['label' => 'Messages', 'route' => 'admin.messages.index', 'description' => "Messages re\u{e7}us via le formulaire de contact.", 'active' => 'admin.messages.*', 'roles' => [UserRole::SuperAdmin->value]],
    ['label' => 'Utilisateurs', 'route' => 'admin.users.index', 'description' => "Comptes, r\u{f4}les et r\u{e9}initialisation des mots de passe.", 'active' => 'admin.users.*', 'roles' => [UserRole::SuperAdmin->value]],
    ['label' => 'Guide', 'route' => 'admin.guide', 'description' => "Prise en main : r\u{f4}le de chaque menu et glossaire.", 'active' => 'admin.guide'],
];
