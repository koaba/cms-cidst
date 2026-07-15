<x-layout>
    <h1 class="text-3xl font-bold mb-6">Blog</h1>

    <div class="grid gap-6">
        @foreach ($articles as $article)
            <a href="{{ route('blog.show', $article) }}" class="block bg-white shadow rounded p-4 hover:shadow-lg">
                <h2 class="text-xl font-semibold">{{ $article->title }}</h2>
                <p class="text-gray-500 text-sm">{{ $article->created_at->format('d/m/Y') }} — {{ $article->user->name }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6">{{ $articles->links() }}</div>
</x-layout>