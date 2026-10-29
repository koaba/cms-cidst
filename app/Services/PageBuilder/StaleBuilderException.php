<?php

namespace App\Services\PageBuilder;

use RuntimeException;

/** La page a change depuis le chargement du builder : sauvegarde refusee. */
class StaleBuilderException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cette page a ete modifiee ailleurs. Rechargez le builder avant d\'enregistrer.');
    }
}
