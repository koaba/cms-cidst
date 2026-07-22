@props(['href' => null, 'type' => 'submit'])

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'inline-block bg-cidst-red hover:bg-cidst-red/90 text-white px-4 py-2 rounded transition-colors']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => 'bg-cidst-red hover:bg-cidst-red/90 text-white px-4 py-2 rounded transition-colors']) }}>
        {{ $slot }}
    </button>
@endif