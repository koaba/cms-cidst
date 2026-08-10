<x-layout>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-cidst-red mb-6">Résultats de recherche</h1>

        <form action="{{ route('search') }}" method="GET" class="mb-8">
            <div class="flex gap-2">
                <input
                    type="text"
                    name="q"
                    value="{{ $query }}"
                    placeholder="Rechercher un article ou une page..."
                    class="flex-1 border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-cidst-red"
                >
                <button type="submit" class="bg-cidst-red text-white px-6 py-2 rounded hover:opacity-90">
                    Rechercher
                </button>
            </div>
        </form>

        @if ($query === '')
            <p class="text-gray-500">Entrez un mot-clé pour lancer une recherche.</p>
        @else
            <p class="text-gray-600 mb-6">
                Résultats pour « {{ $query }} » —
                {{ $articles->count() + $pages->count() }} résultat(s)
            </p>

            @if ($articles->isEmpty() && $pages->isEmpty())
                <p class="text-gray-500">Aucun résultat trouvé.</p>
            @endif

            @if ($articles->isNotEmpty())
                <section class="mb-8">
                    <h2 class="text-lg font-semibold mb-3">Articles</h2>
                    <ul class="space-y-3">
                        @foreach ($articles as $article)
                            <li class="border-b border-gray-200 pb-2">
                              <a href="{{ route('blog.show', $article->slug) }}" class="text-cidst-red hover:underline font-medium">
                                    {{ $article->title }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ $article->published_at?->format('d/m/Y') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($pages->isNotEmpty())
                <section>
                    <h2 class="text-lg font-semibold mb-3">Pages</h2>
                    <ul class="space-y-3">
                        @foreach ($pages as $page)
                            <li class="border-b border-gray-200 pb-2">
                                <a href="{{ route('pages.show', $page) }}" class="text-cidst-red hover:underline font-medium">
                                    {{ $page->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endif
    </div>
</x-layout>