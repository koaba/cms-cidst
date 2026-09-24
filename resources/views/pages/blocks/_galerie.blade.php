@if(($data['layout'] ?? 'grid') === 'carousel')
    @php
        $carouselId = 'carousel-' . $block->id;
        $autoplay = (bool) ($data['autoplay'] ?? false);
        $interval = (int) ($data['autoplay_interval'] ?? 4);
    @endphp
    <div class="relative my-6" id="{{ $carouselId }}" data-autoplay="{{ $autoplay ? '1' : '0' }}" data-interval="{{ $interval }}">
        <div class="carousel-track flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2 scroll-smooth" style="scrollbar-width: none;">
            @foreach($media as $item)
                <figure class="snap-start shrink-0 w-72">
                    <img src="{{ $item->display_url }}" alt="{{ $item->pivot->alt ?? '' }}" loading="lazy" class="w-full h-48 object-cover rounded-lg">
                    @if($item->pivot->caption)
                        <figcaption class="text-sm text-gray-500 mt-1">{{ $item->pivot->caption }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>

        @if($media->count() > 1)
            <button type="button" class="carousel-prev absolute left-0 top-1/2 -translate-y-1/2 bg-white/90 rounded-full p-2 shadow hover:bg-white" aria-label="Image précédente">‹</button>
            <button type="button" class="carousel-next absolute right-0 top-1/2 -translate-y-1/2 bg-white/90 rounded-full p-2 shadow hover:bg-white" aria-label="Image suivante">›</button>

            @if($autoplay)
                <button type="button" class="carousel-toggle absolute bottom-2 right-2 bg-white/90 rounded-full px-3 py-1 shadow hover:bg-white text-sm" aria-label="Mettre en pause le défilement">⏸</button>
            @endif
        @endif
    </div>

    @if($media->count() > 1)
    <script>
    (function () {
        const root = document.getElementById('{{ $carouselId }}');
        const track = root.querySelector('.carousel-track');
        const prevBtn = root.querySelector('.carousel-prev');
        const nextBtn = root.querySelector('.carousel-next');
        const toggleBtn = root.querySelector('.carousel-toggle');

        prevBtn.addEventListener('click', function () {
            track.scrollBy({ left: -300, behavior: 'smooth' });
        });
        nextBtn.addEventListener('click', function () {
            track.scrollBy({ left: 300, behavior: 'smooth' });
        });

        if (!toggleBtn) return;

        const interval = parseInt(root.dataset.interval, 10) * 1000;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let timer = null;

        function scrollNext() {
            const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;
            track.scrollTo({ left: atEnd ? 0 : track.scrollLeft + 300, behavior: 'smooth' });
        }

        function updateToggle(playing) {
            toggleBtn.textContent = playing ? '⏸' : '▶';
            toggleBtn.setAttribute('aria-label', playing ? 'Mettre en pause le défilement' : 'Relancer le défilement');
        }

        function start() {
            if (timer) return;
            timer = setInterval(scrollNext, interval);
            updateToggle(true);
        }

        function stop() {
            clearInterval(timer);
            timer = null;
            updateToggle(false);
        }

        toggleBtn.addEventListener('click', function () {
            timer ? stop() : start();
        });

        root.addEventListener('mouseenter', function () { if (timer) clearInterval(timer); });
        root.addEventListener('mouseleave', function () { if (toggleBtn.textContent === '⏸') timer = setInterval(scrollNext, interval); });

        if (!reduceMotion) {
            start();
        } else {
            updateToggle(false);
        }
    })();
    </script>
    @endif
@else
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 my-6">
        @foreach($media as $item)
            <figure>
                <img src="{{ $item->display_url }}" alt="{{ $item->pivot->alt ?? '' }}" loading="lazy" class="w-full h-40 object-cover rounded-lg">
                @if($item->pivot->caption)
                    <figcaption class="text-sm text-gray-500 mt-1">{{ $item->pivot->caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif