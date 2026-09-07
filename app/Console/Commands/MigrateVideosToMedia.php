<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Mediable;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateVideosToMedia extends Command
{
    protected $signature = 'app:migrate-videos-to-media {--dry-run}';

    protected $description = 'Copie chaque Video existante vers le systeme Media/Mediable unifie, sans toucher a la table videos';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $videos = Video::all();
        $this->info("{$videos->count()} vidéo(s) à migrer.".($dryRun ? ' (dry-run, aucune écriture)' : ''));

        $migrated = 0;

        foreach ($videos as $video) {
            if ($dryRun) {
                $this->line("- [{$video->id}] {$video->title} ({$video->source_type}) -> {$video->videoable_type}#{$video->videoable_id}");

                continue;
            }

            DB::transaction(function () use ($video, &$migrated) {
                $media = Media::create([
                    'type' => 'video',
                    'source_type' => $video->source_type,
                    'path' => $video->path,
                    'url' => $video->url,
                    'original_name' => $video->title,
                    'mime_type' => $video->source_type === 'upload' ? $video->mime : null,
                    'size' => null,
                    'apply_watermark' => $video->apply_watermark,
                ]);

                Mediable::create([
                    'media_id' => $media->id,
                    'mediable_id' => $video->videoable_id,
                    'mediable_type' => $video->videoable_type,
                    'order' => $video->order,
                ]);

                $migrated++;
            });
        }

        if (! $dryRun) {
            $this->info("{$migrated} vidéo(s) migrée(s) avec succès. Table 'videos' laissée intacte.");
        }

        return self::SUCCESS;
    }
}
