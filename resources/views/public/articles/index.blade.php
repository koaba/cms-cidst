<x-layout>
  <h1 class="text-3xl font-display font-bold mb-6 text-cidst-ink">
      @isset($category)
          Articles dans : {{ $category->name }}
      @else
          Blog
      @endisset
  </h1>
@if ($articles->isEmpty())
    <p class="text-cidst-muted">Aucun article pour le moment.</p>
@else
<div class="grid gap-6 sm:grid-cols-2">
    @foreach ($articles as $article)
        <a href="{{ route('blog.show', $article) }}"
           class="flex flex-col h-full bg-cidst-surface border border-cidst-border rounded overflow-hidden hover:shadow-lg transition-shadow">
            @if ($article->image)
                <img src="{{ Storage::url($article->image) }}"
                     alt="{{ $article->title }}"
                     class="w-full aspect-video object-cover">
            @else
                <div class="w-full aspect-video bg-cidst-bg flex items-center justify-center text-cidst-muted text-sm">
                    Aucune image
                </div>
            @endif
            <div class="p-4 flex flex-col flex-1">
                @if ($article->categories->isNotEmpty())
                    <div class="flex flex-wrap gap-1 mb-2">
                        @foreach ($article->categories as $cat)
                            <span class="text-[10px] leading-none bg-cidst-red/10 text-cidst-red px-1.5 py-0.5 rounded-full">
                                {{ $cat->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
                <h2 class="text-lg font-display font-semibold text-cidst-ink line-clamp-2">
                    {{ $article->title }}
                </h2>
                <p class="text-cidst-ink text-sm mt-2 line-clamp-3">
                    {{ Str::limit(strip_tags($article->content), 120) }}
                </p>
                <span class="text-cidst-red text-sm font-medium mt-2">Lire la suite &rarr;</span>
                <p class="text-cidst-muted text-xs mt-auto">
                    {{ $article->published_at?->format('d/m/Y') }} - {{ $article->user->name }}
                </p>
            </div>
        </a>
    @endforeach
</div>
{{ $articles->links() }}
@endif
</x-layout>