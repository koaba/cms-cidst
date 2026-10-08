<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMediaVariants;
use App\Models\Media;
use Illuminate\Console\Command;

class BackfillMediaVariants extends Command
{
    protected $signature = 'media:backfill-variants {--dry-run : Affiche ce qui serait fait sans rien dispatcher}';

    protected $description = 'Dispatche la generation des variantes WebP pour les images qui n\'en ont pas encore';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $queued = 0;

        Media::where('type', 'image')
            ->whereNull('variants')
            ->chunkById(100, function ($medias) use ($dryRun, &$queued) {
                foreach ($medias as $media) {
                    if (! $dryRun) {
                        GenerateMediaVariants::dispatch($media);
                    }

                    $queued++;
                }
            });

        $suffix = $dryRun ? ' (simulation)' : '';

        $this->info("{$queued} image(s) a traiter{$suffix}.");

        return self::SUCCESS;
    }
}
