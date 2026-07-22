<div class="bg-cidst-surface border border-cidst-border rounded shadow-lg py-2 min-w-[180px]">
    @foreach($items as $item)
        @if($item->children->isEmpty())
            <a href="{{ $item->resolved_url }}"
               class="block px-4 py-2 text-sm text-cidst-ink hover:bg-cidst-bg">
                {{ $item->label }}
            </a>
        @else
            <div class="relative group/sub">
                <button class="w-full flex items-center justify-between gap-2 px-4 py-2 text-sm text-cidst-ink hover:bg-cidst-bg">
                    {{ $item->label }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <div class="absolute left-full top-0 pl-1 hidden group-hover/sub:block">
                    @include('components.menu-dropdown-desktop', ['items' => $item->children])
                </div>
            </div>
        @endif
    @endforeach
</div>