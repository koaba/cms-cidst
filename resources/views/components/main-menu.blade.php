<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('blog.index') }}" class="text-xl font-bold text-gray-900">Mon Blog</a>

            {{-- Menu desktop --}}
            <div class="hidden md:flex space-x-6">
                @foreach($items as $item)
                    <a href="{{ $item->resolved_url }}"
                       class="text-gray-600 hover:text-blue-600 transition-colors duration-150 text-sm font-medium">
                        {{ $item->label }}
                    </a>
                @endforeach
            </div>

            {{-- Bouton hamburger mobile --}}
            <button id="mobile-menu-toggle" class="md:hidden text-gray-600" aria-label="Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        {{-- Menu mobile (caché par défaut) --}}
        <div id="mobile-menu" class="hidden md:hidden pb-4 space-y-2">
            @foreach($items as $item)
                <a href="{{ $item->resolved_url }}"
                   class="block text-gray-600 hover:text-blue-600 text-sm font-medium py-1">
                    {{ $item->label }}
                </a>
            @endforeach
        </div>
    </div>
</nav>

<script>
document.getElementById('mobile-menu-toggle')?.addEventListener('click', function () {
    document.getElementById('mobile-menu')?.classList.toggle('hidden');
});
</script>