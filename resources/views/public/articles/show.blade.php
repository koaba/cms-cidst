<x-layout :title="$article->title"> 
    <a href="{{ route('blog.index') }}" class="text-cidst-red hover:underline">&larr; Retour au blog</a>
    <h1 class="text-3xl font-display font-bold mt-4 mb-2 text-cidst-ink">{{ $article->title }}</h1>
    <p class="text-cidst-muted mb-6">{{ $article->published_at?->format('d/m/Y') }} - {{ $article->user->name }}</p>
    @if ($article->image)
        <img src="{{ Storage::url($article->image) }}" alt="{{ $article->title }}" class="w-full max-h-[500px] object-cover rounded mb-6">
    @else
        <div class="w-full aspect-video bg-cidst-bg rounded mb-6 flex items-center justify-center text-cidst-muted text-sm">
            Aucune image
        </div> 
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
    @if ($article->media->isNotEmpty())
        <h2 class="text-xl font-display font-semibold text-cidst-ink mt-8 mb-4">Galerie</h2>
        @if ($article->gallery_display === 'slideshow') 
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2">
                @foreach ($article->media as $media)
                    <img src="{{ Storage::url($media->path) }}"
                         alt="{{ $article->title }} - image {{ $loop->iteration }}"
                         class="w-full max-w-md flex-shrink-0 snap-center aspect-video object-cover rounded">
                @endforeach
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($article->media as $media) 
                    <img src="{{ Storage::url($media->path) }}"
                         alt="{{ $article->title }} - image {{ $loop->iteration }}"
                         class="w-full aspect-square object-cover rounded">
                @endforeach
            </div>
        @endif
    @endif
</x-layout>