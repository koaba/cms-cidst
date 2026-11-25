<?php

namespace App\Enums;

enum BackupStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Running => 'En cours',
            self::Done => "Termin\u{e9}e",
            self::Failed => "\u{c9}chec",
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'badge-warning',
            self::Running => 'badge-info',
            self::Done => 'badge-success',
            self::Failed => 'badge-error',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Running;
    }
}
