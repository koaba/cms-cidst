<?php

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class ProcessBackup extends Command
{
    protected $signature = 'backup:process';

    protected $description = "Traite la plus ancienne sauvegarde demand\u{e9}e depuis l'administration (base + storage/app)";

    public function handle(BackupService $service): int
    {
        Backup::stale()->update([
            'status' => BackupStatus::Failed->value,
            'finished_at' => now(),
            'error' => "Interrompue : d\u{e9}lai d\u{e9}pass\u{e9} ou serveur red\u{e9}marr\u{e9}.",
        ]);

        $backup = Backup::pending()->oldest('id')->first();

        if ($backup === null) {
            return self::SUCCESS;
        }

        $backup->update(['status' => BackupStatus::Running, 'started_at' => now()]);

        try {
            $service->run($backup);
        } catch (Throwable $e) {
            report($e);
            $backup->update([
                'status' => BackupStatus::Failed,
                'finished_at' => now(),
                'error' => Str::limit($e->getMessage(), 500),
            ]);
            $this->error('Sauvegarde en echec : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sauvegarde terminee : '.$backup->directory);

        return self::SUCCESS;
    }
}
