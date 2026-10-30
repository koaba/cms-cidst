<?php

return [

    // Nombre maximum de sliders affichables sur la page d'accueil
    'max_sliders' => 5,

    // Sidebar "actualités"
    'max_sidebar_articles' => 5,

    // Bandeau défilant (news ticker)
    'max_ticker_articles' => 5,
    'ticker_recent_days' => 30,

    // Duree (secondes) du cache du layout public. L'invalidation par observers est
    // immediate ; ce delai ne sert qu'aux changements dus au temps (articles programmes).
    'layout_cache_ttl' => 300,

    // Duree (secondes) du cache de rendu d'une page a blocs. Filet de securite : les
    // observers l'invalident des qu'un bloc, un media, le SEO ou la page change.
    'page_cache_ttl' => 600,

];
