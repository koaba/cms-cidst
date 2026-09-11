<blockquote class="border-l-4 border-gray-300 pl-4 italic text-gray-700">
    <p>{{ $data['content'] }}</p>
    @if(!empty($data['author']))
        <footer class="mt-2 text-sm text-gray-500 not-italic">— {{ $data['author'] }}</footer>
    @endif
</blockquote>