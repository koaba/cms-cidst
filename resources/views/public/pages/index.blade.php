<x-layout>
    <h1 class="text-3xl font-bold mb-6">Pages</h1>

    <div class="grid gap-4">
        @foreach($pages as $page)
            <a href="{{ route('pages.show', $page) }}" class="block border p-4 rounded hover:bg-gray-50">
                <h2 class="text-xl font-semibold">{{ $page->title }}</h2>
            </a>
        @endforeach
    </div>

    {{ $pages->links() }}
</x-layout>