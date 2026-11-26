<?php

namespace App\Enums;

/** Ce qu'une sauvegarde contient : la base, les fichiers (images, PDF, videos, pieces jointes) ou les deux. */
enum BackupScope: string
{
    case All = 'all';
    case Database = 'database';
    case Files = 'files';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Tout',
            self::Database => "Base de donn\u{e9}es",
            self::Files => 'Fichiers',
        };
    }

    public function button(): string
    {
        return match ($this) {
            self::All => 'Tout sauvegarder',
            self::Database => 'Base seule',
            self::Files => 'Fichiers seuls',
        };
    }

    public function includesDatabase(): bool
    {
        return $this !== self::Files;
    }

    public function includesFiles(): bool
    {
        return $this !== self::Database;
    }
}
