<?php

namespace App\Console\Commands;

use App\Services\ExistingImageConverter;
use App\Support\PublicLayout;
use App\Support\PublicPageCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ConvertExistingImagesToWebp extends Command
{
    protected $signature = 'media:convert-webp
        {--force : Convertit vraiment (sans cette option, la commande ne fait que simuler)}
        {--dry-run : Simulation, meme avec --force}
        {--limit= : Nombre maximal de fichiers a traiter}';

    protected $description = 'Convertit en WebP les images JPEG et PNG deja presentes, reecrit les chemins en base et supprime les originaux (faire une sauvegarde avant)';

    public function handle(ExistingImageConverter $converter): int
    {
        $limit = $this->option('limit');

        if ($limit !== null && (! ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('--limit doit etre un entier superieur ou egal a 1.');

            return self::FAILURE;
        }

        $write = (bool) $this->option('force') && ! $this->option('dry-run');
        $paths = $converter->candidates();

        if ($limit !== null) {
            $paths = array_slice($paths, 0, (int) $limit);
        }

        return $write ? $this->convertAll($converter, $paths) : $this->simulate($paths);
    }

    /** @param  list<string>  $paths */
    private function simulate(array $paths): int
    {
        $disk = Storage::disk('public');
        $bytes = 0;

        foreach ($paths as $path) {
            $bytes += $disk->size($path);
            $this->line("Serait converti : {$path}");
        }

        $this->info(count($paths).' image(s) a convertir, '.$this->megabytes($bytes).' Mo au total (simulation).');
        $this->line('Aucune modification. Faire une sauvegarde puis relancer avec --force.');

        return self::SUCCESS;
    }

    /** @param  list<string>  $paths */
    private function convertAll(ExistingImageConverter $converter, array $paths): int
    {
        $disk = Storage::disk('public');
        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $saved = 0;

        foreach ($paths as $path) {
            $before = $disk->size($path);

            try {
                $new = $converter->convert($path);
            } catch (Throwable $e) {
                report($e);
                $this->error("Echec : {$path} ({$e->getMessage()}), original conserve.");
                $failed++;

                continue;
            }

            if ($new === null) {
                $this->line("Ignore (pas de gain ou deja traite) : {$path}");
                $skipped++;

                continue;
            }

            $saved += $before - $disk->size($new);
            $converted++;
            $this->line("Converti : {$path} -> {$new}");
        }

        if ($converted > 0) {
            // Les pages publiques en cache portent encore les anciens chemins.
            PublicPageCache::forgetAll();
            PublicLayout::forget();
        }

        $this->info("{$converted} image(s) convertie(s), {$skipped} ignoree(s), {$failed} en echec.");
        $this->info('Gain : '.$this->megabytes($saved).' Mo.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function megabytes(int $bytes): string
    {
        return number_format($bytes / 1024 / 1024, 2, '.', '');
    }
}
