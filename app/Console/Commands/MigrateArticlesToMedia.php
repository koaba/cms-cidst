<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Media;
use App\Services\MediaDimensionsReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateArticlesToMedia extends Command
{
    protected $signature = 'articles:migrate-to-media {--dry-run : Simule la migration sans rien ecrire}';

    protected $description = 'Cree un Media de couverture (cover_media_id) pour chaque article ayant une image (la colonne image et le fichier sont conserves)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');

        $articles = Article::whereNotNull('image')
            ->where('image', '!=', '')
            ->whereNull('cover_media_id')
            ->get();

        $this->info(sprintf('%d article(s) a traiter.%s', $articles->count(), $dryRun ? ' (dry-run)' : ''));

        $migrated = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            if (! $disk->exists($article->image)) {
                $this->error("Article #{$article->id} : fichier introuvable ({$article->image}), ignore.");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("Article #{$article->id} : serait migre ({$article->image}).");
                $migrated++;

                continue;
            }

            $media = Media::create([
                'path' => $article->image,
                'original_name' => basename($article->image),
                'mime_type' => $disk->mimeType($article->image) ?: 'application/octet-stream',
                'size' => $disk->size($article->image),
                'type' => 'image',
            ] + MediaDimensionsReader::read($article->image));

            $article->coverMedia()->associate($media)->save();

            $this->line("Article #{$article->id} : migre (Media #{$media->id}).");
            $migrated++;
        }

        $this->info("Termine : {$migrated} migre(s), {$skipped} ignore(s).");

        return self::SUCCESS;
    }
}
