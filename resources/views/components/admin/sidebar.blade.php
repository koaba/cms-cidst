@php
$navItems = [
    ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
    ['label' => 'Articles', 'route' => 'admin.articles.index', 'active' => 'admin.articles.*'],
    ['label' => 'Pages', 'route' => 'admin.pages.index', 'active' => 'admin.pages.*'],
    ['label' => 'Catégories', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*'],
    ['label' => 'Médiathèque', 'route' => 'admin.media.index', 'active' => 'admin.media.*'],
    ['label' => 'Sliders', 'route' => 'admin.sliders.index', 'active' => 'admin.sliders.*'],
    ['label' => 'News Ticker', 'route' => 'admin.news-tickers.index', 'active' => 'admin.news-tickers.*'],
    ['label' => 'Menus', 'route' => 'admin.menus.index', 'active' => 'admin.menus.*'],
    ['label' => 'Réglages', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*'],
];
@endphp

<aside class="w-56 bg-gray-800 text-gray-300 min-h-screen p-4 shrink-0">
    <nav class="space-y-1">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="{{ request()->routeIs($item['active']) ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 hover:text-white' }} block px-4 py-2 rounded transition">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>