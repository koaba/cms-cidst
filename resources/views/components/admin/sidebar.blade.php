@php
$navItems = config('admin_menu');
@endphp

<aside id="admin-sidebar"
       class="w-56 bg-admin-nav text-admin-text min-h-screen p-4 shrink-0
              fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform duration-300
              md:static md:translate-x-0">
        @foreach ($navItems as $item)
            @if (!isset($item['roles']) || auth()->user()->hasAnyRole($item['roles']))
                <a href="{{ route($item['route']) }}"
                   class="{{ request()->routeIs($item['active']) ? 'bg-admin-hover text-white' : 'hover:bg-admin-hover hover:text-white' }} block px-4 py-2 rounded transition">
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
</aside>