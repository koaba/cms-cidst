<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Réglages du site — Page d'accueil</h1>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium mb-1">Logo du site</label>
            @if($settings->logo_path)
                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo actuel" class="h-16 mb-2">
            @endif
            <input type="file" name="logo" accept="image/*" class="w-full border rounded p-2">
            <p class="text-xs text-gray-500 mt-1">PNG avec fond transparent recommandé. Laisse vide pour conserver le logo actuel.</p>
            @error('logo') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-medium mb-1">Eyebrow (petit label au-dessus du titre)</label>
            <input type="text" name="hero_eyebrow" value="{{ old('hero_eyebrow', $settings->hero_eyebrow) }}"
                   class="w-full border rounded p-2 mb-2">

            <label class="block text-sm text-gray-600 mb-1">Taille de l'eyebrow</label>
            <select name="hero_eyebrow_size" class="w-full border rounded p-2">
                <option value="xs" {{ old('hero_eyebrow_size', $settings->hero_eyebrow_size) === 'xs' ? 'selected' : '' }}>Très petit</option>
                <option value="sm" {{ old('hero_eyebrow_size', $settings->hero_eyebrow_size) === 'sm' ? 'selected' : '' }}>Petit (par défaut)</option>
                <option value="base" {{ old('hero_eyebrow_size', $settings->hero_eyebrow_size) === 'base' ? 'selected' : '' }}>Normal</option>
                <option value="lg" {{ old('hero_eyebrow_size', $settings->hero_eyebrow_size) === 'lg' ? 'selected' : '' }}>Grand</option>
                <option value="xl" {{ old('hero_eyebrow_size', $settings->hero_eyebrow_size) === 'xl' ? 'selected' : '' }}>Très grand</option>
            </select>
        </div>

        <div>
            <label class="block font-medium mb-1">Titre d'accroche *</label>
            <input type="text" name="hero_title" value="{{ old('hero_title', $settings->hero_title) }}" required
                   class="w-full border rounded p-2">
            @error('hero_title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block font-medium mb-1">Sous-titre</label>
            <textarea name="hero_subtitle" rows="2"
                      class="w-full border rounded p-2">{{ old('hero_subtitle', $settings->hero_subtitle) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-1">Bouton principal — texte</label>
                <input type="text" name="cta_primary_label" value="{{ old('cta_primary_label', $settings->cta_primary_label) }}"
                       class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Bouton principal — cible</label>
                <input type="text" name="cta_primary_target" value="{{ old('cta_primary_target', $settings->cta_primary_target) }}"
                       placeholder="ex: blog.index"
                       class="w-full border rounded p-2">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-1">Bouton secondaire — texte</label>
                <input type="text" name="cta_secondary_label" value="{{ old('cta_secondary_label', $settings->cta_secondary_label) }}"
                       class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Bouton secondaire — cible</label>
                <input type="text" name="cta_secondary_target" value="{{ old('cta_secondary_target', $settings->cta_secondary_target) }}"
                       placeholder="ex: pages.index"
                       class="w-full border rounded p-2">
            </div>
        </div>

        <x-admin.button type="submit">Enregistrer</x-admin.button>
    </form>
</x-admin.layout>