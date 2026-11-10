<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class PruneContactMessages extends Command
{
    protected $signature = 'contact:prune-messages {--days= : Duree de conservation en jours (defaut : contact.message_retention_days)}';

    protected $description = 'Supprime les messages de contact (et leurs images) plus anciens que la duree de conservation';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('contact.message_retention_days'));

        if ($days <= 0) {
            $this->info('Conservation illimitee : rien a supprimer.');

            return self::SUCCESS;
        }

        $count = 0;

        // Suppression par modele : l'evenement `deleting` retire aussi les fichiers des images.
        ContactMessage::query()
            ->where('created_at', '<', now()->subDays($days))
            ->chunkById(100, function (Collection $messages) use (&$count) {
                foreach ($messages as $message) {
                    $message->delete();
                    $count++;
                }
            });

        $this->info("{$count} message(s) supprime(s) (plus de {$days} jours).");

        return self::SUCCESS;
    }
}
