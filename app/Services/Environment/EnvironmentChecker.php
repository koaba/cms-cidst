<?php

namespace App\Services\Environment;

/**
 * Evalue un environnement d'hebergement a partir de sondes deja collectees.
 * Aucune lecture directe de PHP ici : la classe est testable sans dependre de la machine.
 *
 * Niveaux : ok, warning (fonctionnalite degradee mais le site marche), error (le site ne peut pas fonctionner).
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
