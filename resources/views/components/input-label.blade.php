@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-base-content/80']) }}>
    {{ $value ?? $slot }}
</label>
