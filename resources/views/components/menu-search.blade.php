@props(['id', 'inputId'])
{{-- Recherche du menu : un vrai formulaire GET vers /recherche, qui fonctionne sans JavaScript. --}}
<form id="{{ $id }}" method="get" action="{{ route('search') }}" role="search" {{ $attributes->merge(['class' => 'items-center']) }}>
    <label for="{{ $inputId }}" class="sr-only">Rechercher</label>
    <input id="{{ $inputId }}" type="search" name="q" maxlength="255" autocomplete="off" placeholder="Rechercher..."
           class="min-h-[44px] min-w-0 flex-1 rounded border border-cidst-border bg-cidst-surface px-3 text-sm text-cidst-text placeholder:text-cidst-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary md:w-40 md:flex-none">
    <button type="submit" aria-label="Rechercher"
            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-cidst-text transition hover:text-cidst-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
        </svg>
    </button>
</form>
