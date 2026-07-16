<x-layout>
    <h1 class="text-3xl font-bold mb-4">{{ $page->title }}</h1>

    @if($page->image)
        <img src="{{ Storage::url($page->image) }}" class="w-full max-w-2xl mb-4 rounded">
    @endif

    <div class="prose max-w-none">
        {!! nl2br(e($page->content)) !!}
    </div>

    <a href="{{ route('pages.index') }}" class="text-blue-600 mt-4 inline-block">? Retour aux pages</a>
</x-layout>