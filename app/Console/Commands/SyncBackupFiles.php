<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupSettings;
use App\Services\Backup\FileMirror;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class SyncBackupFiles extends Command
{
    protected $signature = 'backup:sync-files';

    protected $description = "Copie miroir de storage/app, ou demande de l'administration (copier / restaurer), hors requete web";

    public function handle(FileMirror $mirror, BackupSettings $settings): int
    {
        $request = $mirror->state()['request'];

        // Ni demande de l'administration ni option activee : rien a faire.
        if ($request === null && ! $settings->mirror()) {
            return self::SUCCESS;
        }

        try {
            $result = $request === FileMirror::RESTORE ? $mirror->restore() : $mirror->sync();
        } catch (Throwable $e) {
            report($e);
            $mirror->fail(Str::limit($e->getMessage(), 300));
            $this->error('Copie miroir en echec : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(($request === FileMirror::RESTORE ? 'Restauration' : 'Copie').' terminee : '.$result['copied'].' fichier(s).');

        return self::SUCCESS;
    }
}
