<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Titre (optionnel)</label>
    <input type="text" name="title" value="{{ $data['title'] ?? old('title') }}" class="w-full border rounded p-2 text-sm">
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Contenu</label>
    <textarea name="content" rows="6" class="w-full border rounded p-2 text-sm">{{ $data['content'] ?? old('content') }}</textarea>
</div>