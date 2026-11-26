<?php

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Services\Backup\BackupSettings;
use Illuminate\Console\Command;

class AutoBackup extends Command
{
    protected $signature = 'backup:auto';

    protected $description = 'Demande la sauvegarde automatique si elle est due (options de Administration > Sauvegardes)';

    public function handle(BackupSettings $settings): int
    {
        // Une sauvegarde deja en cours : on reessaie a l'heure suivante (aucune demande automatique n'est encore comptee).
        if (! $settings->isDue(now()) || Backup::active()->exists()) {
            return self::SUCCESS;
        }

        Backup::create([
            'status' => BackupStatus::Pending,
            'scope' => $settings->all()['scope'],
            'automatic' => true,
        ]);

        $this->info('Sauvegarde automatique demandee.');

        return self::SUCCESS;
    }
}
