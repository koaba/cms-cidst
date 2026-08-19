<?php

return [
    // Chemin du logo filigrane sur le disque "public" (storage/app/public/...)
    'logo_path' => 'watermark/logo-cidst.png',

    // Opacité du filigrane en pourcentage (0-100)
   'opacity' => 25,

    // Taille du filigrane en pourcentage de la largeur du support (image ou page PDF)
    'size_percent' => 7,

    // Position : br (bas-droite), bl (bas-gauche), tr (haut-droite), tl (haut-gauche)
    'position' => 'br',

    // Marge autour du filigrane, en pourcentage de la largeur du support
    'margin_percent' => 2,

    // Activation par défaut par module (utilisé tant qu'il n'y a pas de réglage en base)
    'enabled' => [
        'article_pdf' => true,
        'article_gallery' => true,
        'slider' => true,
    ],
];