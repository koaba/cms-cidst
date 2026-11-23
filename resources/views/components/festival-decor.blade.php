{{-- Decor purement graphique : SVG inline, aucune requete externe, ignore des lecteurs d'ecran et de la souris. --}}
@if($variant === 'logo')
    <span data-festival-decor="{{ $decor }}" data-variant="logo" aria-hidden="true" class="pointer-events-none absolute -top-2 -left-3 h-7 w-7 -rotate-12 print:hidden">
        @include('partials.festival.icons.'.$icons['logo'], ['class' => 'festival-float h-full w-full'])
    </span>
@else
    <div data-festival-decor="{{ $decor }}" data-variant="strip" aria-hidden="true" class="pointer-events-none border-b border-cidst-border bg-cidst-surface print:hidden">
        <div class="mx-auto flex h-9 max-w-6xl items-center justify-around px-4 sm:px-6">
            @foreach($icons['strip'] as $i => $icon)
                @include('partials.festival.icons.'.$icon, ['class' => 'festival-float h-6 w-auto '.($i >= 4 ? 'hidden sm:block' : '')])
            @endforeach
        </div>
    </div>
@endif
