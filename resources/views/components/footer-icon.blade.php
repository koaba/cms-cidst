@props(['name', 'badge' => true, 'tone' => 'text-cidst-primary'])
@php
    // Icones decoratives (traits Heroicons, 24x24) ; le sens est toujours porte par le texte voisin.
    $paths = [
        'pin' => ['M15 10.5a3 3 0 11-6 0 3 3 0 016 0z', 'M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
        'phone' => ['M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z'],
        'chat' => ['M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z'],
        'clock' => ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        'mail' => ['M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
        'facebook' => ['M13.5 21v-8h2.7l.5-3.2h-3.2V7.9c0-.9.3-1.6 1.7-1.6h1.6V3.5c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.1v2.3H7.6V13h2.8v8h3.1z'],
        'link' => ['M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244'],
    ];
    $filled = $name === 'facebook';
@endphp
@if($badge)<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cidst-primary/10">@endif
<svg xmlns="http://www.w3.org/2000/svg" @if($filled) fill="currentColor" @else fill="none" stroke-width="1.5" stroke="currentColor" @endif viewBox="0 0 24 24" class="h-5 w-5 shrink-0 {{ $tone }}" data-icon="{{ $name }}" aria-hidden="true" focusable="false">
    @foreach($paths[$name] ?? [] as $d)
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" />
    @endforeach
</svg>
@if($badge)</span>@endif
