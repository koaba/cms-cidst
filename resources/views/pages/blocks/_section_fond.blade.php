@php
    $bgClasses = [
        'gray' => 'bg-cidst-bg text-cidst-text',
        'blue' => 'bg-blue-600 text-white',
        'dark' => 'bg-cidst-secondary text-white',
    ];
    $bgClass = $bgClasses[$data['bg_color'] ?? 'gray'] ?? $bgClasses['gray'];
@endphp
<div class="{{ $bgClass }} rounded-lg p-8 text-center my-6">
    @if(!empty($data['title']))
        <h2 class="text-2xl font-bold mb-3">{{ $data['title'] }}</h2>
    @endif
    <p class="mb-4">{{ $data['text'] }}</p>
    @if(!empty($data['button_label']) && !empty($data['button_url']))
        <a href="{{ \App\Support\SafeUrl::sanitize($data['button_url']) }}"
           @if(!empty($data['button_new_tab'])) target="_blank" rel="noopener noreferrer" @endif
           class="inline-block px-5 py-2 rounded font-medium bg-white text-cidst-secondary hover:bg-cidst-bg">
            {{ $data['button_label'] }}
        </a>
    @endif
</div>