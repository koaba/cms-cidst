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

    // Cote maximale en pixels (verifiee avant tout decodage).
    'max_side' => 4000,

    'jpeg_quality' => 85,
];
