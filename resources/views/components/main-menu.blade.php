<nav class="bg-cidst-surface border-b border-cidst-border sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex justify-between items-center h-20">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-3">
                <img src="{{ asset('images/logo-cidst.png') }}" alt="CIDST" class="h-12 w-auto">
            </a>

            {{-- Menu desktop --}}
            <div class="hidden md:flex items-center space-x-8">
                @foreach($items as $item)
                    <a href="{{ $item->resolved_url }}"
                       class="relative text-cidst-ink text-sm font-medium tracking-wide
                              after:absolute after:left-0 after:-bottom-1 after:h-[2px] after:w-0
                              after:bg-cidst-red after:transition-all after:duration-300
                              hover:after:w-full">
                        {{ $item->label }}
                    </a>
                @endforeach
            </div>

            {{-- Bouton hamburger mobile --}}
            <button id="mobile-menu-toggle" class="md:hidden text-cidst-ink" aria-label="Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        {{-- Menu mobile --}}
        <div id="mobile-menu" class="hidden md:hidden pb-4 space-y-1 border-t border-cidst-border pt-4">
            @foreach($items as $item)
                <a href="{{ $item->resolved_url }}"
                   class="block text-cidst-ink text-sm font-medium py-2 px-2 rounded hover:bg-cidst-bg">
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