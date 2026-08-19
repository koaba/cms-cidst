<x-layout :seo="$article">
    <a href="{{ route('blog.index') }}" class="text-cidst-red hover:underline">&larr; Retour au blog</a>
    <h1 class="text-3xl font-display font-bold mt-4 mb-2 text-cidst-ink">{{ $article->title }}</h1>
    <p class="text-cidst-muted mb-6">{{ $article->published_at?->format('d/m/Y') }} - {{ $article->user->name }}</p>
    @if ($article->image)
    <img src="{{ Storage::url($article->image) }}" alt="{{ $article->title }}" class="w-full max-h-[500px] object-cover rounded mb-6">
@endif
    @if ($article->categories->isNotEmpty())
        <div class="flex flex-wrap gap-1 mb-6">
            @foreach ($article->categories as $cat)
                <span class="text-[10px] leading-none bg-cidst-red/10 text-cidst-red px-1.5 py-0.5 rounded-full">
                    {{ $cat->name }}
                </span>
            @endforeach
        </div>
    @endif
    <div class="prose">{{ $article->content }}</div>
    @php
        // media() mélange images et PDF depuis l'ajout des documents joints : on sépare ici pour l'affichage.
        $galleryImages = $article->media->filter(fn ($m) => str_starts_with($m->mime_type, 'image/'));
        $attachedPdfs = $article->media->filter(fn ($m) => $m->mime_type === 'application/pdf');
    @endphp
    @if ($galleryImages->isNotEmpty())
        <h2 class="text-xl font-display font-semibold text-cidst-ink mt-8 mb-4">Galerie</h2>
        @if ($article->gallery_display === 'slideshow')
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2">
                @foreach ($galleryImages as $media)
                    <img src="{{ Storage::url($media->path) }}"
                         alt="{{ $article->title }} - image {{ $loop->iteration }}"
                         class="w-full max-w-md flex-shrink-0 snap-center aspect-video object-cover rounded">
                @endforeach
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($galleryImages as $media)
                    <img src="{{ Storage::url($media->path) }}"
                         alt="{{ $article->title }} - image {{ $loop->iteration }}"
                         class="w-full aspect-square object-cover rounded">
                @endforeach
            </div>
        @endif
    @endif

    @if ($attachedPdfs->isNotEmpty())
        <h2 class="text-xl font-display font-semibold text-cidst-ink mt-8 mb-4">Documents joints</h2>
        <ul class="space-y-2 mb-6">
            @foreach ($attachedPdfs as $pdf)
                <li>
                    <a href="{{ Storage::url($pdf->path) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 text-cidst-red hover:underline">
                        <span aria-hidden="true">&#128196;</span>
                        {{ $pdf->original_name }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($article->diaporamas->isNotEmpty())
        <section class="py-10 border-t border-cidst-border">
            <p class="font-mono text-xs text-cidst-muted mb-8">// Diaporamas</p>

            @foreach ($article->diaporamas as $diaporama)
                @if ($diaporama->media->isNotEmpty())
                    <div class="mb-8">
                        @if ($diaporama->title)
                            <h3 class="font-display text-sm font-semibold text-cidst-ink mb-3">{{ $diaporama->title }}</h3>
                        @endif
                        <button type="button"
                                class="diaporama-auto group relative aspect-video w-full max-w-2xl overflow-hidden rounded-lg border border-cidst-border block"
                                data-diaporama="{{ $diaporama->id }}"
                                data-current-index="0">
                            <img src="{{ $diaporama->media->first()->thumbnail_url }}"
         alt="{{ $diaporama->title ?? $article->title }}"
         class="diaporama-auto-img w-full h-full object-cover transition-opacity duration-500">
                            @if ($diaporama->media->count() > 1)
                                <div class="absolute inset-0 flex items-end justify-center pb-3 gap-1.5 pointer-events-none">
                                    @foreach ($diaporama->media as $media)
                                        <span class="diaporama-dot w-1.5 h-1.5 rounded-full transition-colors {{ $loop->first ? 'bg-white' : 'bg-white/50' }}"></span>
                                    @endforeach
                                </div>
                            @endif
                        </button>
                    </div>

                    <script type="application/json" id="diaporama-data-{{ $diaporama->id }}">
    {!! json_encode($diaporama->media->map(fn ($m) => [
    'url' => Storage::url($m->path),
    'thumbnail_url' => $m->thumbnail_url,
    'alt' => $diaporama->title ?? $article->title,
]), JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
                @endif
            @endforeach
        </section>
    @endif

    @if ($article->videos->isNotEmpty())
        <section class="py-10 border-t border-cidst-border">
            <p class="font-mono text-xs text-cidst-muted mb-8">// Vidéos</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($article->videos as $video)
                    <div class="aspect-video rounded-lg overflow-hidden border border-cidst-border bg-cidst-surface">
                        @if ($video->source_type === 'upload')
                            <video controls preload="metadata" class="w-full h-full object-cover">
                                <source src="{{ $video->display_url }}" type="{{ $video->mime }}">
                                Votre navigateur ne supporte pas la lecture vidéo.
                            </video>
                        @else
                            <div class="video-facade relative w-full h-full cursor-pointer group"
                                 data-embed-url="{{ $video->embed_url }}">
                                @if ($video->youtube_thumbnail)
                                    <img src="{{ $video->youtube_thumbnail }}"
                                         alt="{{ $video->title ?? $article->title }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-cidst-ink/90"></div>
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/40 transition-colors">
                                    <div class="w-14 h-14 rounded-full bg-white/90 flex items-center justify-center shadow-lg transition-transform group-hover:scale-110">
                                        <svg class="w-6 h-6 text-cidst-red ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/90 items-center justify-center">
        <button type="button" id="lightbox-close" class="absolute top-4 right-4 text-white text-3xl leading-none">&times;</button>
        <button type="button" id="lightbox-prev" class="absolute left-4 text-white text-4xl leading-none">&lsaquo;</button>
        <img id="lightbox-img" src="" alt="" class="max-h-[85vh] max-w-[90vw] object-contain rounded-lg">
        <button type="button" id="lightbox-next" class="absolute right-4 text-white text-4xl leading-none">&rsaquo;</button>
    </div>

    <script>
        (function () {
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            let currentGroup = [];
            let currentIndex = 0;

            function openLightbox(diaporamaId, index) {
                const dataEl = document.getElementById('diaporama-data-' + diaporamaId);
                if (!dataEl) return;
                currentGroup = JSON.parse(dataEl.textContent);
                currentIndex = index;
                render();
                lightbox.classList.remove('hidden');
                lightbox.classList.add('flex');
            }

            function render() {
                const item = currentGroup[currentIndex];
                lightboxImg.src = item.url;
                lightboxImg.alt = item.alt;
            }

            function close() {
                lightbox.classList.add('hidden');
                lightbox.classList.remove('flex');
            }

            document.getElementById('lightbox-close').addEventListener('click', close);
            document.getElementById('lightbox-prev').addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + currentGroup.length) % currentGroup.length;
                render();
            });
            document.getElementById('lightbox-next').addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % currentGroup.length;
                render();
            });
            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox) close();
            });
            document.addEventListener('keydown', (e) => {
                if (lightbox.classList.contains('hidden')) return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowLeft') document.getElementById('lightbox-prev').click();
                if (e.key === 'ArrowRight') document.getElementById('lightbox-next').click();
            });

            // Diaporama auto-défilant : une seule image visible, change toutes les 4s,
            // clic = ouvre le lightbox à l'image actuellement affichée
            document.querySelectorAll('.diaporama-auto').forEach((container) => {
                const diaporamaId = container.dataset.diaporama;
                const dataEl = document.getElementById('diaporama-data-' + diaporamaId);
                if (!dataEl) return;

                const items = JSON.parse(dataEl.textContent);
                const img = container.querySelector('.diaporama-auto-img');
                const dots = container.querySelectorAll('.diaporama-dot');
                let index = 0;

                if (items.length > 1) {
                    setInterval(() => {
    index = (index + 1) % items.length;
    container.dataset.currentIndex = index;

    img.style.opacity = '0';
    setTimeout(() => {
        img.src = items[index].thumbnail_url;
        img.style.opacity = '1';
    }, 300);

                        dots.forEach((dot, i) => {
                            dot.classList.toggle('bg-white', i === index);
                            dot.classList.toggle('bg-white/50', i !== index);
                        });
                    }, 4000);
                }

                container.addEventListener('click', () => {
                    openLightbox(diaporamaId, parseInt(container.dataset.currentIndex, 10));
                });
            });

            document.querySelectorAll('.video-facade').forEach((facade) => {
                facade.addEventListener('click', () => {
                    const iframe = document.createElement('iframe');
                    iframe.src = facade.dataset.embedUrl + '?autoplay=1';
                    iframe.className = 'w-full h-full';
                    iframe.allow = 'autoplay; fullscreen';
                    iframe.setAttribute('allowfullscreen', '');
                    facade.replaceWith(iframe);
                });
            });
        })();
    </script>
</x-layout>