<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Disposition</label>
    <select name="layout" class="w-full border rounded p-2 text-sm">
        <option value="grid" @selected(($data['layout'] ?? 'grid') === 'grid')>Grille</option>
        <option value="carousel" @selected(($data['layout'] ?? '') === 'carousel')>Carrousel</option>
    </select>
    @error('layout')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

@if(isset($block) && $block->media->isNotEmpty())
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Images actuelles ({{ $block->media->count() }})</label>
        <div class="grid grid-cols-4 gap-2">
            @foreach($block->media as $media)
                <div class="border rounded p-1">
                    <img src="{{ $media->display_url }}" class="w-full h-20 object-cover rounded">
                    @if($media->pivot->caption)
                        <p class="text-xs text-gray-500 mt-1 truncate">{{ $media->pivot->caption }}</p>
                    @endif
                    <label class="flex items-center gap-1 text-xs mt-1 text-red-600">
                        <input type="checkbox" name="delete_media[]" value="{{ $media->id }}">
                        Supprimer
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">
        {{ isset($block) && $block->media->isNotEmpty() ? 'Ajouter des images' : 'Images' }}
        (20 maximum, 5 Mo chacune)
    </label>
    <input type="file" id="galerie-images-input" name="images[]" accept="image/*" multiple class="w-full border rounded p-2 text-sm">
    @error('images')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('images.*')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div id="galerie-images-details" class="space-y-3"></div>

<script>
(function () {
    const input = document.getElementById('galerie-images-input');
    const container = document.getElementById('galerie-images-details');

    input.addEventListener('change', function () {
        container.innerHTML = '';

        Array.from(input.files).forEach(function (file, index) {
            const row = document.createElement('div');
            row.className = 'border rounded p-3';
            row.innerHTML = `
                <p class="text-xs text-gray-500 mb-2">${file.name}</p>
                <label class="block text-xs mb-1">Texte alternatif (alt)</label>
                <input type="text" name="images_alt[${index}]" class="w-full border rounded p-2 text-sm mb-2">
                <label class="block text-xs mb-1">Légende (optionnel)</label>
                <input type="text" name="images_caption[${index}]" class="w-full border rounded p-2 text-sm">
            `;
            container.appendChild(row);
        });
    });
})();
</script>