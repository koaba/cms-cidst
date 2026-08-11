<aside class="bg-cidst-surface border border-cidst-border rounded-xl p-4 sticky top-6 self-start">
    <p class="font-semibold text-sm text-cidst-ink mb-3">Actualités récentes</p>

    @if ($articles->isEmpty())
        <p class="text-xs text-cidst-muted">Aucune actualité pour le moment.</p>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($articles as $article)
                <a href="{{ route('blog.show', $article) }}" class="flex gap-2 items-center group">
                    @if ($article->media->isNotEmpty())
                        <img src="{{ Storage::url($article->media->first()->path) }}"
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
        <div class="border-t border-cidst-border mt-3 pt-3 flex items-center gap-2">
            <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center gap-2 text-xs text-cidst-muted hover:text-cidst-red">
                <span class="w-7 h-7 bg-cidst-red rounded flex items-center justify-center text-white text-xs font-semibold">f</span>
                Suivez le CIDST sur Facebook
            </a>
        </div>
    @endif
</aside>