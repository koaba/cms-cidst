@php
    $styles = [
        'primaire' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondaire' => 'bg-cidst-border text-cidst-text hover:bg-cidst-muted/30',
        'outline' => 'border border-blue-600 text-blue-600 hover:bg-blue-50',
    ];
    $styleClass = $styles[$data['style'] ?? 'primaire'] ?? $styles['primaire'];
@endphp
<a href="{{ \App\Support\SafeUrl::sanitize($data['url']) }}"
   @if(!empty($data['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
   class="inline-block px-5 py-2 rounded font-medium {{ $styleClass }}">
    {{ $data['label'] }}
</a>