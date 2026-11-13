@props(['contact'])

{{-- Encart « Coordonnees » de la page Contact : adresse, telephones, WhatsApp, horaires (jamais l'e-mail,
     deja donne par la phrase de la page) et, avec un point GPS valide, la carte OpenStreetMap chargee au clic. --}}
@if($contact->hasDetails() || $contact->coordinates() !== null)
    <section id="coordonnees" aria-labelledby="coordonnees-title" class="mt-12 border-t border-cidst-border pt-8">
        <h2 id="coordonnees-title" class="text-xl font-display font-semibold text-cidst-text">Coordonnées</h2>

        @if($contact->hasDetails())
            <ul class="mt-4 space-y-2 text-sm text-cidst-text">
                @if($contact->addressLines())
                    <li>
                        <span class="sr-only">Adresse : </span>
                        <address class="not-italic">@foreach($contact->addressLines() as $line){{ $line }}@if(! $loop->last)<br>@endif @endforeach</address>
                    </li>
                @endif
                @foreach($contact->phones() as $phone)
                    <li>
                        <a href="{{ $phone['href'] }}" class="inline-flex min-h-[44px] items-center rounded underline underline-offset-4 transition hover:text-cidst-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">{{ $phone['label'] }}</a>
                    </li>
                @endforeach
                @if($contact->whatsapp())
                    <li>
                        <a href="{{ $contact->whatsapp()['href'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center rounded underline underline-offset-4 transition hover:text-cidst-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">{{ $contact->whatsapp()['label'] }}<span class="ml-2 text-xs text-cidst-muted">&middot; WhatsApp</span><span class="sr-only"> (nouvel onglet)</span></a>
                    </li>
                @endif
                @if($contact->hoursLines())
                    <li>
                        <span class="sr-only">Horaires : </span>
                        <span>@foreach($contact->hoursLines() as $line){{ $line }}@if(! $loop->last)<br>@endif @endforeach</span>
                    </li>
                @endif
            </ul>
        @endif

        @if($contact->coordinates() !== null)
            <div id="nous-trouver" class="mt-6 scroll-mt-24">
                <h3 class="text-base font-semibold text-cidst-text">Nous trouver</h3>
                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <button type="button" data-map-src="{{ $contact->osmEmbedUrl() }}" class="inline-flex min-h-[44px] items-center justify-center rounded-full bg-cidst-primary px-6 text-sm font-semibold text-cidst-primary-content transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Afficher la carte</button>
                    <a href="{{ $contact->directionsUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center justify-center rounded-full border border-cidst-border px-6 text-sm font-semibold text-cidst-text transition hover:bg-cidst-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cidst-primary">Itinéraire<span class="sr-only"> (nouvel onglet)</span></a>
                </div>
                <p class="mt-3 text-xs text-cidst-muted">Carte OpenStreetMap, chargée uniquement après votre clic.</p>
            </div>
            <script>
                document.querySelectorAll('[data-map-src]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var frame = document.createElement('iframe');
                        frame.src = button.getAttribute('data-map-src');
                        frame.title = 'Carte OpenStreetMap';
                        frame.className = 'mt-4 h-72 w-full rounded-lg border border-cidst-border';
                        button.insertAdjacentElement('afterend', frame);
                        button.remove();
                        frame.focus();
                    });
                });
            </script>
        @endif
    </section>
@endif
