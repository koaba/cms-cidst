<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'CIDST' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-news-ticker />
    <x-main-menu />
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        {{ $slot }}
    </main>
</body>
</html>