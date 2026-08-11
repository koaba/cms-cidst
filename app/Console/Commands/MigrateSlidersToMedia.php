<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Slider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateSlidersToMedia extends Command
{
    protected $signature = 'sliders:migrate-to-media {--dry-run : Simule la migration sans rien écrire en base ni sur le disque}';

    protected $description = 'Migre la colonne image des sliders existants vers le système Media/mediables (la colonne image n\'est PAS supprimée par cette commande)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $sliders = Slider::whereNotNull('image')->where('image', '!=', '')->get();

        if ($sliders->isEmpty()) {
            $this->info('Aucun slider avec une image à migrer.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d slider(s) à traiter.%s',
            $sliders->count(),
            $dryRun ? ' (dry-run : rien ne sera écrit)' : ''
        ));
        $this->newLine();

        $migrated = 0;
        $skipped = 0;

        foreach ($sliders as $slider) {
            // Rejouable sans effet de bord : un slider déjà migré est ignoré.
            if ($slider->media()->exists()) {
                $this->line("Slider #{$slider->id} ({$slider->title}) : déjà migré, ignoré.");
                $skipped++;
                continue;
            }

            if (! Storage::disk('public')->exists($slider->image)) {
                $this->error("Slider #{$slider->id} ({$slider->title}) : fichier introuvable sur le disque ({$slider->image}), ignoré.");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("Slider #{$slider->id} ({$slider->title}) : serait migré ({$slider->image}).");
                $migrated++;
                continue;
            }

            $media = Media::create([
                'path' => $slider->image,
                'original_name' => basename($slider->image),
                'mime_type' => Storage::disk('public')->mimeType($slider->image) ?: 'application/octet-stream',
                'size' => Storage::disk('public')->size($slider->image),
            ]);

            $slider->media()->attach($media->id, ['order' => 0]);

            $this->line("Slider #{$slider->id} ({$slider->title}) : migré (Media #{$media->id}).");
            $migrated++;
        }

        $this->newLine();
        $this->info("Terminé : {$migrated} migré(s), {$skipped} ignoré(s).");

        if (! $dryRun) {
            $this->verifyMigration($sliders);
        }

        return self::SUCCESS;
    }

    /**
     * Vérifie que chaque slider traité a bien exactement 1 média attaché.
     * Condition explicitement posée dans le plan avant de toucher au contrôleur.
     */
    private function verifyMigration($sliders): void
    {
        $this->newLine();
        $this->info('Vérification post-migration :');

        $problems = 0;

        foreach ($sliders as $slider) {
            $slider->refresh();
            $count = $slider->media()->count();

            if ($count !== 1) {
                $this->error("⚠ Slider #{$slider->id} ({$slider->title}) : {$count} média(s) attaché(s) au lieu de 1.");
                $problems++;
            }
        }

        if ($problems === 0) {
            $this->info('✓ Chaque slider traité a exactement 1 média attaché.');
        } else {
            $this->error("{$problems} anomalie(s) détectée(s). Ne pas passer à l'étape (c)/(d) tant que ce n'est pas résolu.");
        }
    }
}