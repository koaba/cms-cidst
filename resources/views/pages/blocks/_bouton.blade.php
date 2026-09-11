@php
    $styles = [
        'primaire' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondaire' => 'bg-gray-200 text-gray-800 hover:bg-gray-300',
        'outline' => 'border border-blue-600 text-blue-600 hover:bg-blue-50',
    ];
    $styleClass = $styles[$data['style'] ?? 'primaire'] ?? $styles['primaire'];
@endphp
<a href="{{ $data['url'] }}"
   @if(!empty($data['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
   class="inline-block px-5 py-2 rounded font-medium {{ $styleClass }}">
    {{ $data['label'] }}
</a>