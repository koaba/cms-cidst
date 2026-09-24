<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Titre</label>
    <input type="text" name="titre" value="{{ $data['titre'] ?? old('titre') }}" class="w-full border rounded p-2 text-sm" required>
    @error('titre')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Sous-titre (optionnel)</label>
    <input type="text" name="sous_titre" value="{{ $data['sous_titre'] ?? old('sous_titre') }}" class="w-full border rounded p-2 text-sm">
    @error('sous_titre')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Texte du bouton (optionnel)</label>
    <input type="text" name="bouton_texte" value="{{ $data['bouton_texte'] ?? old('bouton_texte') }}" class="w-full border rounded p-2 text-sm">
    @error('bouton_texte')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">URL du bouton (requis si un texte de bouton est renseigné)</label>
    <input type="url" name="bouton_url" value="{{ $data['bouton_url'] ?? old('bouton_url') }}" class="w-full border rounded p-2 text-sm" placeholder="https://...">
    @error('bouton_url')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">
        Opacité du calque sombre sur l'image
        (<span id="overlay-opacity-valeur">{{ $data['overlay_opacity'] ?? old('overlay_opacity', 40) }}</span>%)
    </label>
    <input
        type="range"
        name="overlay_opacity"
        min="0"
        max="100"
        value="{{ $data['overlay_opacity'] ?? old('overlay_opacity', 40) }}"
        class="w-full"
        oninput="document.getElementById('overlay-opacity-valeur').textContent = this.value"
    >
    <p class="text-xs text-gray-500 mt-1">Un calque sombre améliore la lisibilité du texte posé sur l'image.</p>
    @error('overlay_opacity')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Image de fond</label>
    @if(isset($block) && $block->media->isNotEmpty())
        <img src="{{ Storage::url($block->media->first()->path) }}" class="w-40 rounded mb-2">
        <label class="flex items-center gap-2 text-sm text-red-600 mb-2">
            <input type="checkbox" name="delete_image" value="1">
            Supprimer l'image actuelle
        </label>
        <p class="text-xs text-gray-500 mb-2">Laisse vide pour garder l'image actuelle, ou coche la case pour la retirer.</p>
    @endif
    <input type="file" name="image" accept="image/*" class="w-full border rounded p-2 text-sm">
    @error('image')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    <label class="flex items-center gap-2 text-sm mt-2">
        <input type="checkbox" name="apply_watermark" value="1">
        Appliquer un filigrane
    </label>
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Texte alternatif (alt, pour l'accessibilité et le SEO)</label>
    <input type="text" name="alt" value="{{ $data['alt'] ?? old('alt') }}" class="w-full border rounded p-2 text-sm">
    @error('alt')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
