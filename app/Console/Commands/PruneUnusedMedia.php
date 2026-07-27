<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;

class PruneUnusedMedia extends Command
{
    protected $signature = 'media:prune';

    protected $description = 'Supprime les médias de la médiathèque qui ne sont liés à aucun contenu';

    public function handle()
    {
        $unused = Media::doesntHave('mediables')->get();

        if ($unused->isEmpty()) {
            $this->info('Aucun média orphelin à supprimer.');
            return;
        }

        $count = $unused->count();

        foreach ($unused as $media) {
            $media->delete();
        }

        $this->info("{$count} média(s) orphelin(s) supprimé(s).");
    }
}