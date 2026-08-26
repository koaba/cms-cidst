@props([
    'name',
    'id' => null,
    'label',
    'checked' => false,
    'class' => 'mt-2',
])

@php
    // Sécurité : $name, $label et $id ne doivent jamais provenir d'une entrée
    // utilisateur brute Ã¢â‚¬â€ ce composant est prévu pour des valeurs codées en dur
    // dans les vues admin. {{ }} échappe systématiquement (protection XSS).
    $checkboxId = $id ?? $name;
@endphp

<label for="{{ $checkboxId }}" class="flex items-center gap-2 text-sm text-gray-700 {{ $class }}">
    <input
        type="checkbox"
        id="{{ $checkboxId }}"
        name="{{ $name }}"
        value="1"
        {{ $checked ? 'checked' : '' }}
    >
    {{ $label }}
</label>