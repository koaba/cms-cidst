<footer id="site-footer" class="bg-cidst-surface border-t border-cidst-border text-cidst-muted">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 pb-10">
        {{-- Zone reservee aux icones de fete (lot 3e-bis). --}}
        @if($ctaActions())
            <section id="footer-cta" aria-labelledby="footer-cta-title" class="mb-12 flex flex-col gap-6 rounded-2xl border border-cidst-border bg-cidst-bg p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <h2 id="footer-cta-title" class="text-xl font-semibold text-cidst-text sm:text-2xl">Une question ? Parlons-en</h2>
                    <p class="mt-2 text-sm">Notre équipe est à votre écoute.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    @foreach($ctaActions() as $action)
                        <a href="{{ $action['href'] }}"{!! $action['external'] ? ' target="_blank" rel="noopener noreferrer"' : '' !!} class="inline-flex min-h-[44px] items-center justify-center rounded-full px-6 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary @if($loop->first) bg-cidst-primary text-cidst-primary-content hover:opacity-90 @else border border-cidst-border text-cidst-text hover:bg-cidst-surface @endif">{{ $action['label'] }}@if($action['external'])<span class="sr-only"> (nouvel onglet)</span>@endif</a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
            <div class="sm:col-span-2 @if($hasContacts()) lg:col-span-4 @else lg:col-span-6 @endif">
                <a href="/" class="inline-flex items-center rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">
                    <img src="{{ $logoSrc() }}" alt="Logo" @if($logoSize()) width="{{ $logoSize()['width'] }}" height="{{ $logoSize()['height'] }}" @endif loading="lazy" class="h-14 w-auto">
                </a>
                @if($description())
                    <p class="mt-5 max-w-sm text-sm leading-relaxed">{{ $description() }}</p>
                @endif
            </div>

            <nav aria-label="Pied de page" class="lg:col-span-3">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-cidst-text">Navigation</h2>
                <ul class="mt-4 text-sm">
                    <li>
                        <a href="{{ route('contact.show') }}" class="inline-flex min-h-[44px] items-center rounded transition hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Contact</a>
                    </li>
                    <li>
                        <a href="{{ route('search') }}" class="inline-flex min-h-[44px] items-center rounded transition hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Rechercher</a>
                    </li>
                </ul>
            </nav>

            @if($hasContacts())
                <div class="lg:col-span-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-cidst-text">Nous joindre</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @if($addressLines())
                            <li class="flex items-start gap-4">
                                <x-footer-icon name="pin" />
                                <div class="min-w-0 pt-2">
                                    <span class="sr-only">Adresse : </span>
                                    <address class="not-italic text-cidst-text">@foreach($addressLines() as $line){{ $line }}@if(! $loop->last)<br>@endif @endforeach</address>
                                </div>
                            </li>
                        @endif
                        @foreach($phones() as $phone)
                            <li class="flex items-center gap-4">
                                <x-footer-icon name="phone" />
                                <a href="{{ $phone['href'] }}" class="inline-flex min-h-[44px] items-center rounded text-cidst-text transition hover:text-cidst-primary hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">{{ $phone['label'] }}</a>
                            </li>
                        @endforeach
                        @if($whatsapp())
                            <li class="flex items-center gap-4">
                                <x-footer-icon name="chat" />
                                <a href="{{ $whatsapp()['href'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center rounded text-cidst-text transition hover:text-cidst-primary hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">{{ $whatsapp()['label'] }}<span class="ml-2 text-xs text-cidst-muted">&middot; WhatsApp</span><span class="sr-only"> (nouvel onglet)</span></a>
                            </li>
                        @endif
                        @if($hoursLines())
                            <li class="flex items-start gap-4">
                                <x-footer-icon name="clock" />
                                <div class="min-w-0 pt-2">
                                    <span class="sr-only">Horaires : </span>
                                    <span class="text-cidst-text">@foreach($hoursLines() as $line){{ $line }}@if(! $loop->last)<br>@endif @endforeach</span>
                                </div>
                            </li>
                        @endif
                        @if($email())
                            <li class="flex items-center gap-4">
                                <x-footer-icon name="mail" />
                                <a href="mailto:{{ $email() }}" class="inline-flex min-h-[44px] items-center break-all rounded text-cidst-text transition hover:text-cidst-primary hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">{{ $email() }}</a>
                            </li>
                        @endif
                        @if($facebook())
                            <li class="flex items-center gap-4">
                                <x-footer-icon name="facebook" />
                                <a href="{{ $facebook() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center rounded text-cidst-text transition hover:text-cidst-primary hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Facebook<span class="sr-only"> (nouvel onglet)</span></a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="border-t border-cidst-border">
        <div class="max-w-6xl mx-auto flex flex-col gap-1 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>{{ $copyright() }}</p>
            <a href="#" class="inline-flex min-h-[44px] items-center rounded transition hover:text-cidst-text hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Haut de page</a>
        </div>
    </div>
</footer>
