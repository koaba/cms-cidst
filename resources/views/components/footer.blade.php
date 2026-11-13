<footer id="site-footer" class="bg-cidst-surface border-t border-cidst-border text-cidst-muted">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        {{-- Zone reservee aux icones de fete (lot 3e-bis). --}}
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <a href="/" class="inline-flex items-center rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">
                    <img src="{{ $logoSrc() }}" alt="Logo" @if($logoSize()) width="{{ $logoSize()['width'] }}" height="{{ $logoSize()['height'] }}" @endif loading="lazy" class="h-10 w-auto">
                </a>
                @if($description())
                    <p class="mt-4 text-sm leading-relaxed">{{ $description() }}</p>
                @endif
            </div>

            <nav aria-label="Pied de page">
                <h2 class="text-sm font-semibold text-cidst-text">Navigation</h2>
                <ul class="mt-3 text-sm">
                    <li>
                        <a href="{{ route('contact.show') }}" class="inline-flex min-h-[44px] items-center rounded hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Contact</a>
                    </li>
                    <li>
                        <a href="{{ route('search') }}" class="inline-flex min-h-[44px] items-center rounded hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Rechercher</a>
                    </li>
                </ul>
            </nav>

            @if($email() || $facebook())
                <div>
                    <h2 class="text-sm font-semibold text-cidst-text">Nous joindre</h2>
                    <ul class="mt-3 text-sm">
                        @if($email())
                            <li>
                                <a href="mailto:{{ $email() }}" class="inline-flex min-h-[44px] items-center rounded break-all hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">{{ $email() }}</a>
                            </li>
                        @endif
                        @if($facebook())
                            <li>
                                <a href="{{ $facebook() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center rounded hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Facebook<span class="sr-only"> (nouvel onglet)</span></a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="border-t border-cidst-border">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 text-sm">{{ $copyright() }}</div>
    </div>
</footer>
