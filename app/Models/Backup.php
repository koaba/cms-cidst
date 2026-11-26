<?php

namespace App\Models;

use App\Enums\BackupScope;
use App\Enums\BackupStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;

class Backup extends Model
{
    /** Nom de dossier genere par BackupService (jamais autre chose : garde-fou avant suppression). */
    public const DIRECTORY_PATTERN = '/^\d{8}-\d{6}$/';

    protected $fillable = ['status', 'scope', 'automatic', 'requested_by', 'directory', 'db_size', 'files_size', 'error', 'started_at', 'finished_at'];

    protected $casts = [
        'status' => BackupStatus::class,
        'scope' => BackupScope::class,
        'automatic' => 'boolean',
        'db_size' => 'integer',
        'files_size' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Backup $backup): void {
            if ($backup->directory !== null && preg_match(self::DIRECTORY_PATTERN, $backup->directory)) {
                File::deleteDirectory(self::root().'/'.$backup->directory);
            }
        });
    }

    public static function root(): string
    {
        return rtrim((string) config('backup.path'), '/\\');
    }

    public function filePath(string $file): string
    {
        return self::root().'/'.$this->directory.'/'.$file;
    }

    /** Vrai si cette sauvegarde terminee contient le fichier demande ('database' ou 'files'). */
    public function hasFile(string $kind): bool
    {
        return $this->status === BackupStatus::Done && match ($kind) {
            'database' => $this->scope->includesDatabase(),
            'files' => $this->scope->includesFiles(),
            default => false,
        };
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @param  Builder<Backup>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [BackupStatus::Pending->value, BackupStatus::Running->value]);
    }

    /** @param  Builder<Backup>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', BackupStatus::Pending->value);
    }

    /** @param  Builder<Backup>  $query */
    public function scopeDone(Builder $query): void
    {
        $query->where('status', BackupStatus::Done->value);
    }

    /**
     * Sauvegarde "en cours" depuis trop longtemps : processus mort ou serveur redemarre.
     *
     * @param  Builder<Backup>  $query
     */
    public function scopeStale(Builder $query): void
    {
        $query->where('status', BackupStatus::Running->value)
            ->where('started_at', '<', now()->subSeconds((int) config('backup.timeout') + 300));
    }
}
