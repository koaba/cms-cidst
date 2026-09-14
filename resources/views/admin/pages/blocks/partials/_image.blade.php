<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Image</label>
    @if(isset($block) && $block->media->isNotEmpty())
        <img src="{{ Storage::url($block->media->first()->path) }}" class="w-40 rounded mb-2">
        <p class="text-xs text-gray-500 mb-2">Laisse vide pour garder l'image actuelle.</p>
    @endif
    <input type="file" name="image" accept="image/*" class="w-full border rounded p-2 text-sm">
    @error('image')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Texte alternatif (alt, pour l'accessibilité et le SEO)</label>
    <input type="text" name="alt" value="{{ $data['alt'] ?? old('alt') }}" class="w-full border rounded p-2 text-sm">
    @error('alt')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Légende (optionnel)</label>
    <input type="text" name="caption" value="{{ $data['caption'] ?? old('caption') }}" class="w-full border rounded p-2 text-sm">
    @error('caption')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>