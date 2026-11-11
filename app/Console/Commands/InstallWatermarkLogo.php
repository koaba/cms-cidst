<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class InstallWatermarkLogo extends Command
{
    protected $signature = 'watermark:install-logo {--force : Remplace le logo de filigrane deja present}';

    protected $description = 'Copie le logo de filigrane par defaut sur le disque public (storage/app/public n\'est pas versionne)';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $target = (string) config('watermark.logo_path');

        if ($disk->exists($target) && ! $this->option('force')) {
            $this->info("Logo de filigrane deja present ({$target}) : rien a faire. Utiliser --force pour le remplacer.");

            return self::SUCCESS;
        }

        $source = public_path((string) config('watermark.default_logo'));

        if (! is_file($source)) {
            $this->error("Logo source introuvable : {$source}");

            return self::FAILURE;
        }

        $disk->put($target, (string) file_get_contents($source));
        $this->info("Logo de filigrane installe : {$target}");

        return self::SUCCESS;
    }
}
