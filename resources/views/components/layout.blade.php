<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Mon Blog' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <x-news-ticker />
    <header class="bg-white shadow p-4">
        <a href="{{ route('blog.index') }}" class="text-xl font-bold">Mon Blog</a>
    </header>

    <main class="max-w-4xl mx-auto p-6">
        {{ $slot }}
    </main>
</body>
</html>