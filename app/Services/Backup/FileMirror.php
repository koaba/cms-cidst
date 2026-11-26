<?php

namespace App\Services\Backup;

use Carbon\CarbonImmutable;
use FilesystemIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Number;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Copie miroir incrementale de storage/app vers <backup.mirror.path>/app, et restauration inverse.
 * Regles : le miroir ne supprime jamais rien ; la restauration n'ecrase jamais un fichier existant.
 * L'etat (derniere copie, erreur, demande de l'administration) est un petit fichier JSON dans le miroir.
 */
final class FileMirror
{
    public const SYNC = 'sync';

    public const RESTORE = 'restore';

    public const ACTIONS = [self::SYNC, self::RESTORE];

    /** Dossier de destination du transfert en cours (sert a mesurer l'espace libre). */
    private string $destination = '';

    public function root(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('backup.mirror.path')), '/');
    }

    public function dataDir(): string
    {
        return $this->root().'/app';
    }

    /** @return array{copied: int, bytes: int, files: int, total: int} */
    public function sync(): array
    {
        $this->assertOutsideSource();

        $result = $this->transfer($this->source(), $this->dataDir(), false);

        $this->writeState([
            'synced_at' => now()->toIso8601String(),
            'files' => $result['files'],
            'bytes' => $result['total'],
            'request' => null,
            'error' => null,
        ]);

        return $result;
    }

    /** @return array{copied: int, bytes: int, files: int, total: int} */
    public function restore(): array
    {
        $this->assertOutsideSource();

        if (! is_dir($this->dataDir())) {
            throw new RuntimeException('Aucune copie miroir a restaurer.');
        }

        $result = $this->transfer($this->dataDir(), $this->source(), true);

        $this->writeState(['restored_at' => now()->toIso8601String(), 'request' => null, 'error' => null]);

        return $result;
    }

    /** Enregistre une demande de l'administration, traitee hors requete par backup:sync-files. */
    public function request(string $action): void
    {
        if (! in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException("Action inconnue : {$action}");
        }

        $this->writeState(['request' => $action, 'error' => null]);
    }

    public function fail(string $message): void
    {
        $this->writeState(['request' => null, 'error' => $message]);
    }

    /** @return array{synced_at: ?CarbonImmutable, restored_at: ?CarbonImmutable, files: int, bytes: int, error: ?string, request: ?string} */
    public function state(): array
    {
        $data = $this->readState();
        $request = $data['request'] ?? null;
        $error = $data['error'] ?? null;

        return [
            'synced_at' => $this->date($data['synced_at'] ?? null),
            'restored_at' => $this->date($data['restored_at'] ?? null),
            'files' => (int) ($data['files'] ?? 0),
            'bytes' => (int) ($data['bytes'] ?? 0),
            'error' => is_string($error) ? $error : null,
            'request' => in_array($request, self::ACTIONS, true) ? (string) $request : null,
        ];
    }

    /**
     * Copie vers $to ce qui manque ou est perime. En restauration ($restoring), un fichier deja present n'est jamais touche.
     *
     * @return array{copied: int, bytes: int, files: int, total: int}
     */
    private function transfer(string $from, string $to, bool $restoring): array
    {
        $this->destination = $to;
        $todo = [];
        $size = 0;
        $files = 0;
        $total = 0;

        foreach ($this->files($from) as [$relative, $path]) {
            $files++;
            $total += (int) filesize($path);
            $target = $to.'/'.$relative;

            if ($restoring) {
                if (is_file($target)) {
                    continue;
                }
            } elseif ($this->isUpToDate($path, $target)) {
                continue;
            }

            $todo[] = [$relative, $path];
            $size += (int) filesize($path);
        }

        if ($todo !== []) {
            $this->guardDisk($size);
        }

        foreach ($todo as [$relative, $path]) {
            $this->copyFile($path, $to.'/'.$relative, $restoring);
        }

        return ['copied' => count($todo), 'bytes' => $size, 'files' => $files, 'total' => $total];
    }

    private function isUpToDate(string $path, string $target): bool
    {
        return is_file($target)
            && filesize($target) === filesize($path)
            && filemtime($target) >= filemtime($path);
    }

    /** Copie via un fichier .part puis renommage : une coupure ne laisse jamais un fichier tronque. */
    private function copyFile(string $path, string $target, bool $restoring): void
    {
        File::ensureDirectoryExists(dirname($target), $restoring ? 0755 : 0700);

        $partial = $target.'.part';

        if (! @copy($path, $partial)) {
            @unlink($partial);

            throw new RuntimeException('Copie impossible : '.basename($path));
        }

        @touch($partial, (int) filemtime($path));

        if (! $restoring) {
            @chmod($partial, 0600);
        }

        rename($partial, $target);
    }

    private function guardDisk(int $needed): void
    {
        $dir = $this->destination;

        while (! is_dir($dir) && dirname($dir) !== $dir) {
            $dir = dirname($dir);
        }

        $free = @disk_free_space($dir);

        if ($free !== false && $free < $needed + (int) config('backup.mirror.reserve')) {
            throw new RuntimeException(sprintf(
                'Espace disque insuffisant : %s a copier, %s libres.',
                Number::fileSize($needed),
                Number::fileSize($free),
            ));
        }
    }

    private function source(): string
    {
        return rtrim(str_replace('\\', '/', (string) config('backup.storage_path')), '/');
    }

    /** Le miroir ne doit jamais se trouver dans la source : il se copierait lui-meme sans fin. */
    private function assertOutsideSource(): void
    {
        $source = $this->source();
        $mirror = $this->root();

        if ($mirror === '' || $mirror === $source || str_starts_with($mirror.'/', $source.'/')) {
            throw new RuntimeException('Le dossier miroir doit etre hors de storage/app (BACKUP_MIRROR_PATH).');
        }
    }

    /** @return list<array{0: string, 1: string}> paires [chemin relatif, chemin complet] */
    private function files(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $base = strlen($dir) + 1;
        $list = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME)
        );

        foreach ($iterator as $entry) {
            $path = str_replace('\\', '/', (string) $entry);

            if (is_link($path) || ! is_file($path) || str_ends_with($path, '.part')) {
                continue;
            }

            $list[] = [substr($path, $base), $path];
        }

        return $list;
    }

    /** @return array<string, mixed> */
    private function readState(): array
    {
        $file = $this->root().'/etat.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) ? $data : [];
    }

    /** @param  array<string, mixed>  $patch */
    private function writeState(array $patch): void
    {
        File::ensureDirectoryExists($this->root(), 0700);

        file_put_contents(
            $this->root().'/etat.json',
            json_encode([...$this->readState(), ...$patch], JSON_THROW_ON_ERROR),
            LOCK_EX,
        );
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return is_string($value) ? CarbonImmutable::parse($value)->setTimezone((string) config('app.timezone')) : null;
    }
}
