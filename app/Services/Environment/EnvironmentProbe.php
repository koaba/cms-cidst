<?php

namespace App\Services\Environment;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Lit l'etat reel du serveur et le met sous la forme attendue par EnvironmentChecker.
 * Attention : en ligne de commande, php.ini peut differer de celui du serveur web.
 */
class EnvironmentProbe
{
    public function collect(): array
    {
        $disabled = $this->disabledFunctions();

        return [
            'php_version' => PHP_VERSION,
            'extensions' => array_map('strtolower', get_loaded_extensions()),
            'gd_webp' => $this->gdWebp(),
            'disabled_functions' => $disabled,
            'memory_limit' => (string) ini_get('memory_limit'),
            'max_execution_time' => (int) ini_get('max_execution_time'),
            'writable' => [
                'storage' => is_writable(storage_path()),
                'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            ],
            'database_ok' => $this->databaseOk(),
            'pdftoppm' => ! in_array('proc_open', $disabled, true) && $this->pdftoppmAvailable(),
            'imagick' => extension_loaded('imagick'),
            'app_url' => (string) config('app.url'),
            'app_env' => (string) config('app.env'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function disabledFunctions(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions')))));
    }

    private function gdWebp(): bool
    {
        return function_exists('gd_info') && (bool) (gd_info()['WebP Support'] ?? false);
    }

    private function databaseOk(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function pdftoppmAvailable(): bool
    {
        try {
            $process = new Process([(string) config('services.pdf_thumbnail.poppler_binary', 'pdftoppm'), '-v']);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }
}
