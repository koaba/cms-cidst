<?php

namespace App\Services\Environment;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Lit l'etat reel du serveur et le met sous la forme attendue par EnvironmentChecker.
 * Attention : en ligne de commande, php.ini peut differer de celui du serveur web.
 */
class EnvironmentProbe
{
    public const SCHEDULER_HEARTBEAT_KEY = 'scheduler:heartbeat';

    public function collect(): array
    {
        $disabled = $this->disabledFunctions();

        return [
            'php_version' => PHP_VERSION,
            'extensions' => array_map('strtolower', get_loaded_extensions()),
            'gd_webp' => $this->gdSupport('WebP Support'),
            'gd_jpeg' => $this->gdSupport('JPEG Support'),
            'gd_png' => $this->gdSupport('PNG Support'),
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size' => (string) ini_get('post_max_size'),
            ...$this->contactImages(),
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
            'app_debug' => (bool) config('app.debug'),
            'mail_mailer' => (string) config('mail.default'),
            'mail_from' => (string) config('mail.from.address'),
            'queue_connection' => (string) config('queue.default'),
            'queue_stuck' => $this->stuckJobs(),
            'now' => time(),
            'scheduler_heartbeat' => $this->heartbeat(),
            'storage_link' => file_exists(public_path('storage')),
            'watermark_logo' => $this->watermarkLogo(),
            'csp_mode' => (string) config('security.csp.mode'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function disabledFunctions(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions')))));
    }

    /**
     * Taches en file (pilote database) disponibles depuis plus de 10 minutes et non reservees :
     * signe qu'aucun worker ne les traite. Null quand l'etat n'est pas verifiable.
     */
    private function stuckJobs(): ?int
    {
        if (config('queue.default') !== 'database') {
            return null;
        }

        try {
            $table = (string) config('queue.connections.database.table', 'jobs');

            return Schema::hasTable($table)
                ? DB::table($table)->whereNull('reserved_at')->where('available_at', '<=', time() - 600)->count()
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dernier battement ecrit par le planificateur (routes/console.php), en timestamp Unix.
     */
    private function heartbeat(): ?int
    {
        try {
            $value = Cache::get(self::SCHEDULER_HEARTBEAT_KEY);

            return is_numeric($value) ? (int) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function gdSupport(string $feature): bool
    {
        return function_exists('gd_info') && (bool) (gd_info()[$feature] ?? false);
    }

    /**
     * Reglages des images jointes du formulaire de contact (desactivees si la base est inaccessible).
     *
     * @return array{contact_images_enabled: bool, contact_images_max_mb: int, contact_images_max_files: int}
     */
    private function contactImages(): array
    {
        try {
            $settings = SiteSetting::current();
            $enabled = (bool) $settings->contact_images_enabled;
            $maxMb = (int) $settings->contact_images_max_mb;
        } catch (\Throwable) {
            $enabled = false;
            $maxMb = 0;
        }

        return [
            'contact_images_enabled' => $enabled,
            'contact_images_max_mb' => $maxMb,
            'contact_images_max_files' => (int) config('contact.max_files'),
        ];
    }

    /** Le logo de filigrane vit dans storage/app/public, qui n'est pas versionne. */
    private function watermarkLogo(): bool
    {
        try {
            return Storage::disk('public')->exists((string) config('watermark.logo_path'));
        } catch (\Throwable) {
            return false;
        }
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
