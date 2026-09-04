<aside class="bg-cidst-surface border border-cidst-border rounded-xl p-4 sticky top-6 self-start">
    <p class="font-semibold text-sm text-cidst-ink mb-3">Actualités récentes</p>
    @if ($articles->isEmpty())
        <p class="text-xs text-cidst-muted">Aucune actualité pour le moment.</p>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($articles as $article)
                <a href="{{ route('blog.show', $article) }}" class="flex gap-2 items-center group">
                @if ($article->image)
                    <img src="{{ Storage::url($article->image) }}"
                    alt="{{ $article->title }}"
                    class="w-11 h-11 object-cover rounded flex-shrink-0">
                @else
    <div class="w-11 h-11 bg-cidst-bg rounded flex-shrink-0"></div>
@endif
                    <div>
                        <p class="text-xs text-cidst-ink line-clamp-2 group-hover:underline">{{ $article->title }}</p>
                        <p class="text-[10px] text-cidst-muted mt-0.5">{{ $article->published_at?->format('d/m') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    @if ($facebookUrl)
        <div class="border-t border-cidst-border mt-3 pt-3">
            <p class="text-xs font-medium text-cidst-ink mb-2">Suivez-nous sur Facebook</p>
            <div class="rounded-lg overflow-hidden">
                <iframe
                    src="https://www.facebook.com/plugins/page.php?href={{ urlencode($facebookUrl) }}&tabs=timeline&width=280&height=400&small_header=true&adapt_container_width=true&hide_cover=false&show_facepile=false&lazy=true"
                    width="100%"
                    height="400"
                    style="border:none; overflow:hidden;"
                    scrolling="no"
                    frameborder="0"
                    allowfullscreen="true"
                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                    loading="lazy">
                </iframe>
            </div>
        </div>
    @endif
</aside>