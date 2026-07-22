<div class="pl-4 space-y-1">
    @foreach($items as $item)
        @if($item->children->isEmpty())
            <a href="{{ $item->resolved_url }}"
               class="block text-cidst-ink text-sm py-2 px-2 rounded hover:bg-cidst-bg">
                {{ $item->label }}
            </a>
        @else
            <details class="px-2">
                <summary class="text-cidst-ink text-sm py-2 cursor-pointer">
                    {{ $item->label }}
                </summary>
                @include('components.menu-dropdown-mobile', ['items' => $item->children])
            </details>
        @endif
    @endforeach
</div>