<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sauvegardes demandees depuis l'administration
    |--------------------------------------------------------------------------
    | Dossier HORS de storage/app (sinon l'archive s'inclurait elle-meme) et hors de public.
    | Traitees par `backup:process` (planifiee chaque minute) : aucune sauvegarde ne tourne dans
    | une requete web. Distinct des sauvegardes root de scripts/install-serveur.sh (/var/backups/cidst).
    */

    'path' => env('BACKUP_PATH', storage_path('backups')),

    // Dossier archive (par defaut storage/app).
    'storage_path' => storage_path('app'),

    // Connexion a sauvegarder (null = connexion par defaut).
    'connection' => null,

    // Nombre de sauvegardes terminees conservees (les plus anciennes sont supprimees).
    'keep' => (int) env('BACKUP_KEEP', 7),

    // Duree maximale d'une sauvegarde, en secondes. Au-dela, elle est declaree en echec.
    'timeout' => (int) env('BACKUP_TIMEOUT', 3600),

];
