<x-layout :title="$article->title">
    <a href="{{ route('blog.index') }}" class="text-blue-600">&larr; Retour au blog</a>

    <h1 class="text-3xl font-bold mt-4 mb-2">{{ $article->title }}</h1>
    <p class="text-gray-500 mb-6">{{ $article->created_at->format('d/m/Y') }} — {{ $article->user->name }}</p>

    @if ($article->image)
        <img src="{{ Storage::url($article->image) }}" class="w-full rounded mb-6">
    @endif

    <div class="prose">{{ $article->content }}</div>
</x-layout>