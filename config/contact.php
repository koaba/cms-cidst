<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Images jointes au formulaire de contact
    |--------------------------------------------------------------------------
    | Activation et taille maximale : Reglages du site (Super Admin).
    | Les images sont reencodees avec GD puis rangees sur le disque prive.
    */

    'max_files' => 3,

    // Cote maximale en pixels (verifiee avant tout decodage). 3000 px couvre une photo de telephone
    // reduite ; au-dela, le decodage GD (environ 4 octets par pixel) menace la memoire de PHP.
    'max_side' => 3000,

    'jpeg_quality' => 85,

    // Duree de conservation des images, en jours (0 = illimitee). Voir contact:prune-images.
    'retention_days' => (int) env('CONTACT_IMAGES_RETENTION_DAYS', 90),
];
