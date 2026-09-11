<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Texte du bouton</label>
    <input type="text" name="label" value="{{ $data['label'] ?? old('label') }}" class="w-full border rounded p-2 text-sm">
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Lien (URL)</label>
    <input type="text" name="url" value="{{ $data['url'] ?? old('url') }}" placeholder="https://... ou /pages/..." class="w-full border rounded p-2 text-sm">
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Style</label>
    <select name="style" class="w-full border rounded p-2 text-sm">
        <option value="primaire" @selected(($data['style'] ?? 'primaire') === 'primaire')>Primaire</option>
        <option value="secondaire" @selected(($data['style'] ?? '') === 'secondaire')>Secondaire</option>
        <option value="outline" @selected(($data['style'] ?? '') === 'outline')>Outline</option>
    </select>
</div>

<div class="mb-4">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="new_tab" value="1" @checked($data['new_tab'] ?? false)>
        Ouvrir dans un nouvel onglet
    </label>
</div>