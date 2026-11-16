@props(['menu'])
<li>
    <a href="{{ $menu->resolved_url }}"{!! $menu->is_external ? ' target="_blank" rel="noopener noreferrer"' : '' !!} class="inline-flex min-h-[44px] items-center rounded transition hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">{{ $menu->label }}@if($menu->is_external)<span class="sr-only"> (nouvel onglet)</span>@endif</a>
</li>
