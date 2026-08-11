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
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="flex-1 min-w-0">
                {{ $slot }}
            </div>
            <div class="w-full lg:w-72 flex-shrink-0">
                <x-news-sidebar />
            </div>
        </div>
    </main>
</body>
</html>