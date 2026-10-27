<?php

return [
    ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
    ['label' => 'Articles', 'route' => 'admin.articles.index', 'active' => 'admin.articles.*'],
    ['label' => 'Pages', 'route' => 'admin.pages.index', 'active' => 'admin.pages.*'],
    ['label' => 'Catégories', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'roles' => ['Super Admin']],
    ['label' => 'Médiathèque', 'route' => 'admin.media.index', 'active' => 'admin.media.*'],
    ['label' => 'Sliders', 'route' => 'admin.sliders.index', 'active' => 'admin.sliders.*'],
    ['label' => 'Documents PDF', 'route' => 'admin.pdf-documents.index', 'active' => 'admin.pdf-documents.*', 'roles' => ['Super Admin']],
    ['label' => 'Catégories PDF', 'route' => 'admin.pdf-categories.index', 'active' => 'admin.pdf-categories.*', 'roles' => ['Super Admin']],
    ['label' => 'News Ticker', 'route' => 'admin.news-tickers.index', 'active' => 'admin.news-tickers.*'],
    ['label' => 'Menus', 'route' => 'admin.menus.index', 'active' => 'admin.menus.*', 'roles' => ['Super Admin']],
    ['label' => 'Réglages', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'roles' => ['Super Admin']],
    ['label' => 'Messages', 'route' => 'admin.messages.index', 'active' => 'admin.messages.*', 'roles' => ['Super Admin']],
    ['label' => 'Utilisateurs', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'roles' => ['Super Admin']],
];
