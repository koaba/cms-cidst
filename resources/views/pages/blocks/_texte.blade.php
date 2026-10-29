@php
    $alignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];
    $alignClass = $alignClasses[$data['design']['align'] ?? ''] ?? '';
@endphp
<div class="prose max-w-none {{ $alignClass }}">
    @if(!empty($data['title']))
        <h2 class="text-xl font-semibold mb-2">{{ $data['title'] }}</h2>
    @endif
    {!! nl2br(e($data['content'])) !!}
</div>