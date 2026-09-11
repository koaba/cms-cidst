<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Style du séparateur</label>
    <select name="style" class="w-full border rounded p-2 text-sm">
        <option value="fin" @selected(($data['style'] ?? 'fin') === 'fin')>Trait fin</option>
        <option value="epais" @selected(($data['style'] ?? '') === 'epais')>Trait épais</option>
    </select>
</div>