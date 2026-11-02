<?php

use App\Support\VideoEmbed;

it('resout les urls YouTube et Vimeo en url embed', function (string $url, string $attendu) {
    expect(VideoEmbed::embedUrl($url))->toBe($attendu);
})->with([
    'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'watch avec parametres' => ['https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=10', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ?t=5', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'nocookie' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'sans schema' => ['youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
    'vimeo' => ['https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871'],
    'vimeo player' => ['https://player.vimeo.com/video/76979871', 'https://player.vimeo.com/video/76979871'],
]);

it('refuse tout ce qui n\'est pas YouTube ou Vimeo', function (?string $url) {
    expect(VideoEmbed::embedUrl($url))->toBeNull();
})->with([
    'javascript' => ['javascript:alert(1)'],
    'data' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
    'autre site' => ['https://example.com/video.mp4'],
    'hote leurre dans la requete' => ['https://evil.example/?u=youtube.com/watch?v=dQw4w9WgXcQ'],
    'hote leurre par identifiants' => ['https://youtube.com@evil.example/watch?v=dQw4w9WgXcQ'],
    'domaine suffixe' => ['https://notyoutube.com/watch?v=dQw4w9WgXcQ'],
    'identifiant trop court' => ['https://youtube.com/watch?v=abc'],
    'identifiant aux caracteres invalides' => ['https://youtube.com/watch?v=dQw4w9WgX"<'],
    'vide' => [''],
    'null' => [null],
]);

it('extrait l\'identifiant YouTube pour la miniature', function () {
    expect(VideoEmbed::youtubeId('https://youtu.be/dQw4w9WgXcQ'))->toBe('dQw4w9WgXcQ')
        ->and(VideoEmbed::youtubeId('https://vimeo.com/76979871'))->toBeNull();
});
