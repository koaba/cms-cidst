@props(['media', 'alt' => '', 'sizes' => '100vw', 'priority' => false, 'class' => ''])

@php
    $original = $media->display_url;
    $srcset = $media->srcset;
    $hasDimensions = $media->width && $media->height;
    $extra = $attributes->toHtml();
@endphp
<img src="{{ $original }}" alt="{{ $alt }}" @if($srcset !== '') srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif @if($hasDimensions) width="{{ $media->width }}" height="{{ $media->height }}" @endif @if($priority) fetchpriority="high" @else loading="lazy" @endif decoding="async" class="{{ $class }}" {!! $extra !!}>