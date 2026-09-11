<div class="prose max-w-none">
    @if(!empty($data['title']))
        <h2 class="text-xl font-semibold mb-2">{{ $data['title'] }}</h2>
    @endif
    {!! nl2br(e($data['content'])) !!}
</div>