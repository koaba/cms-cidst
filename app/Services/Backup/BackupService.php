<?php

namespace App\Services\Backup;

use App\Enums\BackupStatus;
use App\Models\Backup;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Fabrique une sauvegarde (base + storage/app) dans <backup.path>/AAAAMMJJ-HHMMSS/.
 * Meme format que scripts/install-serveur.sh : base.sql.gz et storage-app.tgz, droits 700/600.
 */
final class BackupService
{
    public const DATABASE_FILE = 'base.sql.gz';

    public const STORAGE_FILE = 'storage-app.tgz';

    public function run(Backup $backup): void
    {
        $name = now()->format('Ymd-His');
        $dir = Backup::root().'/'.$name;

        File::ensureDirectoryExists(Backup::root(), 0700);
        File::ensureDirectoryExists($dir, 0700);

        try {
            $this->dumpDatabase($dir.'/'.self::DATABASE_FILE);
            $this->archiveStorage($dir.'/'.self::STORAGE_FILE);
            @chmod($dir.'/'.self::DATABASE_FILE, 0600);
            @chmod($dir.'/'.self::STORAGE_FILE, 0600);
        } catch (\Throwable $e) {
            File::deleteDirectory($dir);

            throw $e;
        }

        $backup->update([
            'status' => BackupStatus::Done,
            'directory' => $name,
            'db_size' => (int) filesize($dir.'/'.self::DATABASE_FILE),
            'files_size' => (int) filesize($dir.'/'.self::STORAGE_FILE),
            'finished_at' => now(),
            'error' => null,
        ]);

        $this->prune();
    }

    /** Ne garde que les `backup.keep` dernieres sauvegardes terminees et les echecs recents. */
    public function prune(): void
    {
        $kept = Backup::done()->latest('id')->limit(max(1, (int) config('backup.keep')))->pluck('id');

        Backup::done()->whereNotIn('id', $kept)->get()->each->delete();

        Backup::where('status', BackupStatus::Failed->value)->where('created_at', '<', now()->subDays(30))->get()->each->delete();
    }

    private function dumpDatabase(string $target): void
    {
        $name = (string) (config('backup.connection') ?? config('database.default'));
        $config = (array) config("database.connections.{$name}");

        match ($config['driver'] ?? null) {
            'mysql', 'mariadb' => $this->dumpMysql($config, $target),
            'sqlite' => $this->dumpSqlite((string) $config['database'], $target),
            default => throw new RuntimeException('Pilote de base non pris en charge pour la sauvegarde : '.($config['driver'] ?? '?')),
        };
    }

    /** @param  array<string, mixed>  $config */
    private function dumpMysql(array $config, string $target): void
    {
        $gz = gzopen($target, 'wb9');

        // Le mot de passe passe par l'environnement du processus, jamais dans la ligne de commande.
        $process = new Process(
            ['mysqldump', '--single-transaction', '--routines', '--no-tablespaces',
                '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'], (string) $config['database']],
            null,
            ['MYSQL_PWD' => (string) $config['password']],
            null,
            (float) config('backup.timeout'),
        );

        // Ecriture au fil de l'eau : un dump volumineux ne passe jamais entier en memoire.
        $process->run(function (string $type, string $buffer) use ($gz, $process): void {
            if ($type === Process::OUT) {
                gzwrite($gz, $buffer);
                $process->clearOutput();
            }
        });
        gzclose($gz);

        if (! $process->isSuccessful()) {
            throw new RuntimeException("mysqldump a \u{e9}chou\u{e9} : ".trim($process->getErrorOutput()));
        }
    }

    private function dumpSqlite(string $path, string $target): void
    {
        $in = @fopen($path, 'rb');

        if ($in === false) {
            throw new RuntimeException("Fichier SQLite introuvable : {$path}");
        }

        $gz = gzopen($target, 'wb9');

        while (! feof($in)) {
            gzwrite($gz, (string) fread($in, 1 << 20));
        }

        gzclose($gz);
        fclose($in);
    }

    private function archiveStorage(string $target): void
    {
        $source = rtrim((string) config('backup.storage_path'), '/\\');

        // Chemin relatif depuis le dossier cible : un chemin C:\... serait pris pour un hote distant par un tar GNU (Git Bash sous Windows).
        $process = new Process(['tar', '-czf', basename($target), '-C', dirname($source), basename($source)], dirname($target), null, null, (float) config('backup.timeout'));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Archive des fichiers impossible : '.trim($process->getErrorOutput()));
        }
    }
}
