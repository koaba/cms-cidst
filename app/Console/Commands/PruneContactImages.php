<?php

namespace App\Console\Commands;

use App\Models\ContactAttachment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class PruneContactImages extends Command
{
    protected $signature = 'contact:prune-images {--days= : Duree de conservation en jours (defaut : contact.retention_days)}';

    protected $description = 'Supprime les images jointes plus anciennes que la duree de conservation (les messages restent)';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('contact.retention_days'));

        if ($days <= 0) {
            $this->info('Conservation illimitee : rien a supprimer.');

            return self::SUCCESS;
        }

        $count = 0;

        ContactAttachment::query()
            ->where('created_at', '<', now()->subDays($days))
            ->chunkById(100, function (Collection $attachments) use (&$count) {
                foreach ($attachments as $attachment) {
                    $attachment->delete();
                    $count++;
                }
            });

        $this->info("{$count} image(s) supprimee(s) (plus de {$days} jours).");

        return self::SUCCESS;
    }
}
