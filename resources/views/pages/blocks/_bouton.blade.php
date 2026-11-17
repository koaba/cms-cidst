@php
    $styles = [
        'primaire' => 'bg-cidst-primary text-cidst-primary-content hover:bg-cidst-primary/90',
        'secondaire' => 'bg-cidst-border text-cidst-text hover:bg-cidst-muted/30',
        'outline' => 'border border-cidst-primary text-cidst-primary hover:bg-cidst-primary/10',
    ];
    $styleClass = $styles[$data['style'] ?? 'primaire'] ?? $styles['primaire'];
@endphp
<a href="{{ \App\Support\SafeUrl::sanitize($data['url']) }}"
   @if(!empty($data['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
   class="inline-block px-5 py-2 rounded font-medium {{ $styleClass }}">
    {{ $data['label'] }}
</a>