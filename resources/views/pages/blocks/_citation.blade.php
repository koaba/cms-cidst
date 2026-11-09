<blockquote class="border-l-4 border-cidst-border pl-4 italic text-cidst-ink">
    <p>{{ $data['content'] }}</p>
    @if(!empty($data['author']))
        <footer class="mt-2 text-sm text-cidst-muted not-italic">— {{ $data['author'] }}</footer>
    @endif
</blockquote>