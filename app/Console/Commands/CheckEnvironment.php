<?php

namespace App\Console\Commands;

use App\Services\Environment\EnvironmentChecker;
use App\Services\Environment\EnvironmentProbe;
use Illuminate\Console\Command;

class CheckEnvironment extends Command
{
    protected $signature = 'app:check-environment';

    protected $description = 'Verifie que le serveur reunit la configuration necessaire a l\'application (PHP, extensions, dossiers, base)';

    private const LABELS = ['ok' => 'OK', 'warning' => 'AVERTISSEMENT', 'error' => 'ERREUR'];

    public function handle(): int
    {
        $results = (new EnvironmentChecker(app(EnvironmentProbe::class)->collect()))->check();

        foreach ($results as $result) {
            $line = sprintf('[%s] %s', self::LABELS[$result['level']], $result['message']);

            match ($result['level']) {
                'ok' => $this->info($line),
                'warning' => $this->warn($line),
                default => $this->error($line),
            };
        }

        $errors = count(array_filter($results, fn ($r) => $r['level'] === 'error'));
        $warnings = count(array_filter($results, fn ($r) => $r['level'] === 'warning'));

        if ($errors > 0) {
            $this->error("{$errors} probleme(s) bloquant(s), {$warnings} avertissement(s).");

            return self::FAILURE;
        }

        $this->info("Environnement utilisable ({$warnings} avertissement(s)).");

        return self::SUCCESS;
    }
}
