<?php

namespace App\Support;

/**
 * Convertit un lien video saisi par un utilisateur en URL d'integration (iframe).
 *
 * Seuls YouTube et Vimeo sont acceptes. L'URL renvoyee est toujours reconstruite
 * a partir d'un identifiant valide : le texte saisi n'est jamais repris tel quel.
 */
final class VideoEmbed
{
    private const YOUTUBE_HOSTS = [
        'youtube.com', 'www.youtube.com', 'm.youtube.com',
        'youtube-nocookie.com', 'www.youtube-nocookie.com',
    ];

    private const VIMEO_HOSTS = ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'];

    public static function embedUrl(?string $url): ?string
    {
        if ($id = self::youtubeId($url)) {
            return 'https://www.youtube.com/embed/'.$id;
        }

        if ($id = self::vimeoId($url)) {
            return 'https://player.vimeo.com/video/'.$id;
        }

        return null;
    }

    public static function youtubeId(?string $url): ?string
    {
        $parts = self::parse($url);

        if ($parts === null) {
            return null;
        }

        if ($parts['host'] === 'youtu.be') {
            return self::validYoutubeId(explode('/', ltrim($parts['path'], '/'))[0]);
        }

        if (! in_array($parts['host'], self::YOUTUBE_HOSTS, true)) {
            return null;
        }

        if ($parts['path'] === '/watch') {
            parse_str($parts['query'], $query);

            return self::validYoutubeId($query['v'] ?? null);
        }

        if (preg_match('#^/(?:embed|shorts)/([^/]+)#', $parts['path'], $m)) {
            return self::validYoutubeId($m[1]);
        }

        return null;
    }

    private static function vimeoId(?string $url): ?string
    {
        $parts = self::parse($url);

        if ($parts === null || ! in_array($parts['host'], self::VIMEO_HOSTS, true)) {
            return null;
        }

        return preg_match('#^/(?:video/)?(\d+)#', $parts['path'], $m) ? $m[1] : null;
    }

    private static function validYoutubeId(mixed $id): ?string
    {
        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }

    /**
     * @return array{host: string, path: string, query: string}|null
     */
    private static function parse(?string $url): ?array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        $parts = parse_url($url);

        if ($parts === false
            || ! isset($parts['host'])
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        return [
            'host' => strtolower($parts['host']),
            'path' => $parts['path'] ?? '',
            'query' => $parts['query'] ?? '',
        ];
    }
}
