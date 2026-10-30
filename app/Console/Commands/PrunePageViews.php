<?php

namespace App\Console\Commands;

use App\Models\PageView;
use App\Services\Dashboard\DashboardPeriod;
use Illuminate\Console\Command;

class PrunePageViews extends Command
{
    /** Nombre de lignes supprimees par requete, pour ne pas verrouiller la table. */
    private const BATCH_SIZE = 5000;

    protected $signature = 'page-views:prune {--months= : Conserver les N derniers mois (defaut : fenetre maximale de la periode Global)}';

    protected $description = 'Supprime les vues de pages plus anciennes que la fenêtre conservée';

    public function handle(): int
    {
        $option = $this->option('months');
        $months = $option === null
            ? DashboardPeriod::GLOBAL_MAX_MONTHS
            : filter_var($option, FILTER_VALIDATE_INT);

        if ($months === false || $months < 1) {
            $this->error('--months doit être un entier supérieur ou égal à 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subMonths($months);
        $total = 0;

        do {
            $deleted = PageView::query()
                ->where('viewed_at', '<', $cutoff)
                ->limit(self::BATCH_SIZE)
                ->delete();
            $total += $deleted;
        } while ($deleted === self::BATCH_SIZE);

        $this->info("{$total} vue(s) antérieure(s) au {$cutoff->format('Y-m-d')} supprimée(s).");

        return self::SUCCESS;
    }
}
