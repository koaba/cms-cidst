<x-layout>
    <h1 class="text-3xl font-display font-bold mb-6 text-cidst-ink">Blog</h1>
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($articles as $article)
        <a href="{{ route('blog.show', $article) }}"
           class="block bg-cidst-surface border border-cidst-border rounded overflow-hidden hover:shadow-lg transition-shadow">
            @if ($article->image)
                <img src="{{ Storage::url($article->image) }}"
                     alt="{{ $article->title }}"
                     class="w-full h-48 object-cover">
            @else
                <div class="w-full h-48 bg-cidst-bg flex items-center justify-center text-cidst-muted text-sm">
                    Aucune image
                </div>
            @endif
            <div class="p-4">
                <h2 class="text-xl font-display font-semibold text-cidst-ink">{{ $article->title }}</h2>
                <p class="text-cidst-muted text-sm mt-1">
                    {{ $article->created_at->format('d/m/Y') }} — {{ $article->user->name }}
                </p>
            </div>
        </a>
    @endforeach
</div>
<div class="mt-6">{{ $articles->links() }}</div>
</x-layout>