<?php

namespace App\Console\Commands;

use App\Services\Backup\FileMirror;
use Illuminate\Console\Command;
use RuntimeException;

class RestoreBackupFiles extends Command
{
    public const QUESTION = "Copier vers storage/app les fichiers du miroir qui y manquent ? Aucun fichier existant n'est ecrase.";

    protected $signature = 'backup:restore-files {--force : Sans confirmation}';

    protected $description = 'Restaure dans storage/app les fichiers absents, depuis la copie miroir (jamais d\'ecrasement)';

    public function handle(FileMirror $mirror): int
    {
        if (! $this->option('force') && ! $this->confirm(self::QUESTION)) {
            return self::FAILURE;
        }

        try {
            $result = $mirror->restore();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Restauration terminee : '.$result['copied'].' fichier(s) restaure(s).');

        return self::SUCCESS;
    }
}
