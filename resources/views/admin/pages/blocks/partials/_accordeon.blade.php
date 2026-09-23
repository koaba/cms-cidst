<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Titre (optionnel)</label>
    <input type="text" name="title" value="{{ $data['title'] ?? old('title') }}" class="w-full border rounded p-2 text-sm">
    @error('title')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

@if(isset($data))
    <p class="text-sm text-gray-500 border-t pt-3 mt-3">
        Les items de l'accordéon se gèrent depuis la page de modification du bloc, une fois celui-ci enregistré.
    </p>
@endif