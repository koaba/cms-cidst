<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Titre (optionnel)</label>
    <input type="text" name="title" value="{{ $data['title'] ?? old('title') }}" class="w-full border rounded p-2 text-sm">
    @error('title')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Texte</label>
    <textarea name="text" rows="4" class="w-full border rounded p-2 text-sm">{{ $data['text'] ?? old('text') }}</textarea>
    @error('text')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Couleur de fond</label>
    <select name="bg_color" class="w-full border rounded p-2 text-sm">
        <option value="gray" @selected(($data['bg_color'] ?? 'gray') === 'gray')>Gris clair</option>
        <option value="blue" @selected(($data['bg_color'] ?? '') === 'blue')>Bleu</option>
        <option value="dark" @selected(($data['bg_color'] ?? '') === 'dark')>Sombre</option>
    </select>
    @error('bg_color')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="border-t pt-4 mt-4">
    <p class="text-sm font-medium mb-2">Bouton (optionnel)</p>

    <div class="mb-4">
        <label class="block text-sm mb-1">Texte du bouton</label>
        <input type="text" name="button_label" value="{{ $data['button_label'] ?? old('button_label') }}" class="w-full border rounded p-2 text-sm">
        @error('button_label')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label class="block text-sm mb-1">Lien (URL)</label>
        <input type="text" name="button_url" value="{{ $data['button_url'] ?? old('button_url') }}" class="w-full border rounded p-2 text-sm">
        @error('button_url')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="button_new_tab" value="1" @checked($data['button_new_tab'] ?? false)>
        Ouvrir le bouton dans un nouvel onglet
    </label>
    @error('button_new_tab')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>