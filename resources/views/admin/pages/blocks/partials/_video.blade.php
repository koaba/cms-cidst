<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Titre (optionnel)</label>
    <input type="text" name="title" value="{{ $data['title'] ?? old('title') }}" class="w-full border rounded p-2 text-sm">
    @error('title') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Source de la vidéo</label>
    <select name="source_type" class="w-full border rounded p-2 text-sm"
        onchange="document.getElementById('video-upload-field').classList.toggle('hidden', this.value !== 'upload'); document.getElementById('video-url-field').classList.toggle('hidden', this.value !== 'url');">
        <option value="upload" @selected(($data['source_type'] ?? 'upload') === 'upload')>Fichier uploadé (MP4/WebM)</option>
        <option value="url" @selected(($data['source_type'] ?? '') === 'url')>Lien externe (YouTube, Vimeo...)</option>
    </select>
    @error('source_type') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div id="video-upload-field" class="mb-4 {{ ($data['source_type'] ?? 'upload') !== 'upload' ? 'hidden' : '' }}">
    @if(isset($block) && $block->media->isNotEmpty())
        <p class="text-xs text-gray-500 mb-1">Fichier actuel : {{ basename($block->media->first()->path) }}</p>
        <label class="flex items-center gap-2 text-sm text-red-600 mb-2">
            <input type="checkbox" name="delete_video" value="1">
            Supprimer le fichier vidéo actuel
        </label>
    @endif
    <input type="file" name="video_file" accept="video/mp4,video/webm" class="w-full border rounded p-2 text-sm">
    @error('video_file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    <p class="text-xs text-gray-500 mt-1">Laisse vide pour garder le fichier actuel (MP4/WebM, 15 Mo max).</p>
</div>

<div id="video-url-field" class="mb-4 {{ ($data['source_type'] ?? 'upload') !== 'url' ? 'hidden' : '' }}">
    <input type="url" name="url" value="{{ $data['url'] ?? old('url') }}" placeholder="https://youtube.com/..." class="w-full border rounded p-2 text-sm">
    @error('url') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>