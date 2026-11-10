<?php

namespace App\Services\Environment;

/**
 * Evalue un environnement d'hebergement a partir de sondes deja collectees.
 * Aucune lecture directe de PHP ici : la classe est testable sans dependre de la machine.
 *
 * Niveaux : ok, warning (fonctionnalite degradee mais le site marche), error (bloquant : le site ne peut pas fonctionner,
 * ou ne doit pas etre mis en ligne en l'etat).
 *
 * Les controles de production (APP_DEBUG, courrier, file, cron, storage, CSP) ne s'appliquent que si app_env = production.
 */
class EnvironmentChecker
{
    public const MIN_PHP = '8.3.0';

    public const MIN_MEMORY_BYTES = 134217728; // 128M

    public const MIN_EXECUTION_SECONDS = 30;

    public const REQUIRED_EXTENSIONS = [
        'curl', 'dom', 'fileinfo', 'filter', 'hash', 'iconv', 'json', 'libxml', 'openssl',
        'pcre', 'phar', 'session', 'simplexml', 'tokenizer', 'xml', 'xmlwriter', 'zlib', 'gd',
    ];

    public const DATABASE_EXTENSIONS = ['pdo_mysql', 'pdo_sqlite'];

    public const MAX_HEARTBEAT_SECONDS = 600;

    public function __construct(private readonly array $probe) {}

    /**
     * @return array<int, array{code: string, level: string, message: string}>
     */
    public function check(): array
    {
        return [
            $this->phpVersion(),
            $this->extensions(),
            $this->gdWebp(),
            $this->writable(),
            $this->database(),
            $this->procOpen(),
            $this->pdfThumbnail(),
            $this->memoryLimit(),
            $this->executionTime(),
            $this->appUrl(),
            $this->appDebug(),
            $this->mail(),
            $this->queue(),
            $this->scheduler(),
            $this->storageLink(),
            $this->csp(),
        ];
    }

    private function phpVersion(): array
    {
        $version = (string) ($this->probe['php_version'] ?? '0');

        return version_compare($version, self::MIN_PHP, '>=')
            ? $this->result('php_version', 'ok', "PHP {$version}")
            : $this->result('php_version', 'error', "PHP {$version} : version ".self::MIN_PHP.' minimum requise.');
    }

    private function extensions(): array
    {
        $loaded = (array) ($this->probe['extensions'] ?? []);
        $missing = array_values(array_diff(self::REQUIRED_EXTENSIONS, $loaded));

        if (array_intersect(self::DATABASE_EXTENSIONS, $loaded) === []) {
            $missing[] = implode(' ou ', self::DATABASE_EXTENSIONS);
        }

        return $missing === []
            ? $this->result('extensions', 'ok', 'Extensions requises presentes.')
            : $this->result('extensions', 'error', 'Extensions manquantes : '.implode(', ', $missing).'.');
    }

    private function gdWebp(): array
    {
        return ($this->probe['gd_webp'] ?? false)
            ? $this->result('gd_webp', 'ok', 'GD avec support WebP.')
            : $this->result('gd_webp', 'error', 'GD sans support WebP : les variantes d\'images ne peuvent pas etre generees.');
    }

    private function writable(): array
    {
        $blocked = array_keys(array_filter((array) ($this->probe['writable'] ?? []), fn ($ok) => ! $ok));

        return $blocked === []
            ? $this->result('writable', 'ok', 'Dossiers d\'ecriture accessibles.')
            : $this->result('writable', 'error', 'Dossiers non inscriptibles : '.implode(', ', $blocked).'.');
    }

    private function database(): array
    {
        return ($this->probe['database_ok'] ?? false)
            ? $this->result('database', 'ok', 'Connexion a la base de donnees.')
            : $this->result('database', 'error', 'Connexion a la base de donnees impossible.');
    }

    private function procOpen(): array
    {
        return in_array('proc_open', (array) ($this->probe['disabled_functions'] ?? []), true)
            ? $this->result('proc_open', 'warning', 'proc_open est desactive : les miniatures PDF serveur sont indisponibles (la miniature navigateur reste utilisee).')
            : $this->result('proc_open', 'ok', 'proc_open disponible.');
    }

    private function pdfThumbnail(): array
    {
        return ($this->probe['pdftoppm'] ?? false) || ($this->probe['imagick'] ?? false)
            ? $this->result('pdf_thumbnail', 'ok', 'Generateur de miniatures PDF serveur disponible.')
            : $this->result('pdf_thumbnail', 'warning', 'Ni pdftoppm ni Imagick : seules les miniatures PDF generees par le navigateur seront utilisees.');
    }

    private function memoryLimit(): array
    {
        $raw = (string) ($this->probe['memory_limit'] ?? '0');
        $bytes = $this->toBytes($raw);

        return $bytes === -1 || $bytes >= self::MIN_MEMORY_BYTES
            ? $this->result('memory_limit', 'ok', "memory_limit = {$raw}")
            : $this->result('memory_limit', 'warning', "memory_limit = {$raw} : 128M minimum recommande.");
    }

    private function executionTime(): array
    {
        $seconds = (int) ($this->probe['max_execution_time'] ?? 0);

        return $seconds === 0 || $seconds >= self::MIN_EXECUTION_SECONDS
            ? $this->result('max_execution_time', 'ok', "max_execution_time = {$seconds}")
            : $this->result('max_execution_time', 'warning', "max_execution_time = {$seconds} s : ".self::MIN_EXECUTION_SECONDS.' s minimum recommande.');
    }

    private function appUrl(): array
    {
        if (($this->probe['app_env'] ?? null) !== 'production') {
            return $this->result('app_url', 'ok', 'APP_URL non controlee hors production.');
        }

        $url = trim((string) ($this->probe['app_url'] ?? ''));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));

        $suspect = $url === ''
            || $scheme !== 'https'
            || $host === ''
            || $host === 'localhost'
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || preg_match('/\.(localhost|test|local)$/', $host) === 1;

        return $suspect
            ? $this->result('app_url', 'warning', "APP_URL = {$url} : en production, une URL https avec un vrai nom de domaine est attendue (sinon og:image, sitemap et robots.txt sont faux).")
            : $this->result('app_url', 'ok', "APP_URL = {$url}");
    }

    private function production(): bool
    {
        return ($this->probe['app_env'] ?? null) === 'production';
    }

    private function notChecked(string $code, string $label): array
    {
        return $this->result($code, 'ok', "{$label} non controle hors production.");
    }

    private function appDebug(): array
    {
        if (! $this->production()) {
            return $this->notChecked('app_debug', 'APP_DEBUG');
        }

        return ($this->probe['app_debug'] ?? false)
            ? $this->result('app_debug', 'error', 'APP_DEBUG est actif en production : les erreurs exposent la configuration et les secrets.')
            : $this->result('app_debug', 'ok', 'APP_DEBUG desactive.');
    }

    private function mail(): array
    {
        if (! $this->production()) {
            return $this->notChecked('mail', 'Courrier');
        }

        $mailer = strtolower(trim((string) ($this->probe['mail_mailer'] ?? '')));
        $from = strtolower(trim((string) ($this->probe['mail_from'] ?? '')));
        $problems = [];

        if (in_array($mailer, ['log', 'array'], true)) {
            $problems[] = "MAIL_MAILER = {$mailer} : les alertes de contact ne partent pas, elles sont seulement ecrites dans le journal";
        }

        if ($from === '' || str_ends_with($from, '@example.com')) {
            $problems[] = 'MAIL_FROM_ADDRESS est vide ou factice (example.com)';
        }

        return $problems === []
            ? $this->result('mail', 'ok', "Courrier configure ({$mailer}).")
            : $this->result('mail', 'warning', implode(' ; ', $problems).'.');
    }

    private function queue(): array
    {
        if (! $this->production()) {
            return $this->notChecked('queue', 'File d\'attente');
        }

        $connection = (string) ($this->probe['queue_connection'] ?? '');
        $stuck = $this->probe['queue_stuck'] ?? null;

        if ($connection === 'sync') {
            return $this->result('queue', 'ok', 'File d\'attente en mode sync : traitement immediat, aucun worker requis (les variantes d\'images sont generees pendant la requete).');
        }

        if ($stuck === null) {
            return $this->result('queue', 'warning', "File d'attente '{$connection}' : l'etat du worker n'est pas verifiable. Verifiez qu'un worker tourne (sinon les variantes d'images ne sont pas generees).");
        }

        return $stuck > 0
            ? $this->result('queue', 'warning', "{$stuck} tache(s) en attente depuis plus de 10 minutes : le worker ne tourne pas, les variantes d'images ne sont pas generees.")
            : $this->result('queue', 'ok', 'Aucune tache en attente depuis plus de 10 minutes.');
    }

    private function scheduler(): array
    {
        if (! $this->production()) {
            return $this->notChecked('scheduler', 'Planificateur');
        }

        $beat = $this->probe['scheduler_heartbeat'] ?? null;

        if ($beat === null) {
            return $this->result('scheduler', 'warning', 'Planificateur : aucun battement enregistre. Le cron `php artisan schedule:run` est absent ou n\'a pas encore tourne.');
        }

        $age = (int) ($this->probe['now'] ?? time()) - (int) $beat;
        $minutes = intdiv(max($age, 0), 60);

        return $age > self::MAX_HEARTBEAT_SECONDS
            ? $this->result('scheduler', 'warning', "Planificateur : dernier battement il y a {$minutes} min (10 min maximum) : le cron est arrete ou trop espace.")
            : $this->result('scheduler', 'ok', "Planificateur actif (dernier battement il y a {$minutes} min).");
    }

    private function storageLink(): array
    {
        if (! $this->production()) {
            return $this->notChecked('storage_link', 'Lien public/storage');
        }

        return ($this->probe['storage_link'] ?? false)
            ? $this->result('storage_link', 'ok', 'Lien public/storage present.')
            : $this->result('storage_link', 'warning', 'public/storage absent : lancer `php artisan storage:link`, sinon les fichiers televerses ne s\'affichent pas.');
    }

    private function csp(): array
    {
        if (! $this->production()) {
            return $this->notChecked('csp', 'CSP');
        }

        $mode = strtolower((string) ($this->probe['csp_mode'] ?? ''));

        return $mode === 'enforce'
            ? $this->result('csp', 'ok', 'CSP appliquee (enforce).')
            : $this->result('csp', 'warning', "CSP non appliquee (mode {$mode}) : observer la console du navigateur puis passer CSP_MODE=enforce.");
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function result(string $code, string $level, string $message): array
    {
        return ['code' => $code, 'level' => $level, 'message' => $message];
    }
}
