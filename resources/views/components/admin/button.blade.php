@props(['href' => null, 'type' => 'submit', 'variant' => 'primary', 'size' => 'md'])

@php
    $variants = [
        'primary' => 'btn btn-primary text-white',
        'secondary' => 'btn btn-secondary text-white',
        'outline' => 'btn btn-outline',
        'ghost' => 'btn btn-ghost',
        'error' => 'btn btn-error text-white',
        'danger' => 'btn btn-error text-white',
        'link' => 'btn btn-link',
    ];
    $sizes = ['xs' => 'btn-xs', 'sm' => 'btn-sm', 'md' => '', 'lg' => 'btn-lg'];
    $classes = trim(($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif