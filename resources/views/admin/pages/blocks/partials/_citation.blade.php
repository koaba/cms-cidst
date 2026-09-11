<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Citation</label>
    <textarea name="content" rows="4" class="w-full border rounded p-2 text-sm">{{ $data['content'] ?? old('content') }}</textarea>
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Auteur (optionnel)</label>
    <input type="text" name="author" value="{{ $data['author'] ?? old('author') }}" class="w-full border rounded p-2 text-sm">
</div>