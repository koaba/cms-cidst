<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <nav class="bg-gray-800 text-white p-4 flex justify-between items-center gap-4 sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <button id="admin-sidebar-toggle" class="md:hidden p-2 -ml-2 rounded hover:bg-gray-700" aria-label="Ouvrir le menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="font-bold">CMS Admin</span>
        </div>
        <div class="flex gap-4 items-center flex-wrap justify-end text-sm">
            <span>{{ auth()->user()->name }} ({{ auth()->user()->getRoleNames()->first() }})</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline">Déconnexion</button>
            </form>
        </div>
    </nav>

    <div class="flex">
        <div id="admin-sidebar-overlay" class="hidden fixed inset-0 bg-black/50 z-30 md:hidden"></div>

        <x-admin.sidebar />

        <main class="p-6 flex-1 min-w-0">
            {{ $slot }}
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle  = document.getElementById('admin-sidebar-toggle');
            const sidebar = document.getElementById('admin-sidebar');
            const overlay = document.getElementById('admin-sidebar-overlay');

            function openSidebar() {
                sidebar?.classList.remove('-translate-x-full');
                overlay?.classList.remove('hidden');
            }
            function closeSidebar() {
                sidebar?.classList.add('-translate-x-full');
                overlay?.classList.add('hidden');
            }

            toggle?.addEventListener('click', () => {
                sidebar?.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
            });
            overlay?.addEventListener('click', closeSidebar);

            // Fermer automatiquement après un clic sur un lien (mobile)
            sidebar?.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) closeSidebar();
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>