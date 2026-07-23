<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <nav class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <div class="flex gap-6 items-center">
        <span class="font-bold">CMS Admin</span>
       <a href="{{ route('admin.articles.index') }}" class="hover:underline">Articles</a>
        <a href="{{ route('admin.categories.index') }}" class="hover:underline">Catégories</a>
    </div>
    <div class="flex gap-4 items-center">
        <span>{{ auth()->user()->name }} ({{ auth()->user()->getRoleNames()->first() }})</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline">Déconnexion</button>
        </form>
    </div>
</nav>

    <main class="p-6">
        {{ $slot }}
    </main>
</body>
</html>