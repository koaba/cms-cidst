<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaDimensionsReader;
use Illuminate\Console\Command;

class BackfillMediaDimensions extends Command
{
    protected $signature = 'media:backfill-dimensions {--dry-run : Affiche ce qui serait fait sans rien ecrire}';

    protected $description = 'Renseigne width et height des images de la mediatheque qui n\'en ont pas encore';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;
        $missing = 0;

        Media::where('type', 'image')
            ->whereNull('width')
            ->chunkById(100, function ($medias) use ($dryRun, &$processed, &$missing) {
                foreach ($medias as $media) {
                    $dimensions = MediaDimensionsReader::read($media->path);

                    if ($dimensions === []) {
                        $missing++;

                        continue;
                    }

                    if (! $dryRun) {
                        $media->update($dimensions);
                    }

                    $processed++;
                }
            });

        $suffix = $dryRun ? ' (simulation)' : '';

        $this->info("{$processed} traite(s){$suffix}.");
        $this->info("{$missing} introuvable(s).");

        return self::SUCCESS;
    }
}
