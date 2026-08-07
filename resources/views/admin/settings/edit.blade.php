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

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-1">Couleur principale</label>
                <input type="color" name="primary_color" value="{{ old('primary_color', $settings->primary_color) }}"
                       class="w-full h-10 border rounded p-1">
                @error('primary_color') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block font-medium mb-1">Couleur secondaire</label>
                <input type="color" name="secondary_color" value="{{ old('secondary_color', $settings->secondary_color) }}"
                       class="w-full h-10 border rounded p-1">
                @error('secondary_color') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block font-medium mb-1">Direction du bandeau d'actualités</label>
            <select name="news_ticker_direction" class="w-full border rounded p-2">
                <option value="horizontal" {{ old('news_ticker_direction', $settings->news_ticker_direction) === 'horizontal' ? 'selected' : '' }}>Horizontal (défilement latéral)</option>
                <option value="vertical" {{ old('news_ticker_direction', $settings->news_ticker_direction) === 'vertical' ? 'selected' : '' }}>Vertical (défilement du bas vers le haut)</option>
            </select>
            @error('news_ticker_direction') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
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

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-1">Nombre de colonnes (grille Pages)</label>
                <select name="pages_grid_columns" class="w-full border rounded p-2">
                    <option value="2" {{ old('pages_grid_columns', $settings->pages_grid_columns) == 2 ? 'selected' : '' }}>2 colonnes</option>
                    <option value="3" {{ old('pages_grid_columns', $settings->pages_grid_columns) == 3 ? 'selected' : '' }}>3 colonnes</option>
                    <option value="4" {{ old('pages_grid_columns', $settings->pages_grid_columns) == 4 ? 'selected' : '' }}>4 colonnes</option>
                </select>
                @error('pages_grid_columns') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block font-medium mb-1">Taille des images (grille Pages)</label>
                <select name="pages_image_size" class="w-full border rounded p-2">
                    <option value="small" {{ old('pages_image_size', $settings->pages_image_size) === 'small' ? 'selected' : '' }}>Petite</option>
                    <option value="medium" {{ old('pages_image_size', $settings->pages_image_size) === 'medium' ? 'selected' : '' }}>Moyenne</option>
                    <option value="large" {{ old('pages_image_size', $settings->pages_image_size) === 'large' ? 'selected' : '' }}>Grande</option>
                </select>
                @error('pages_image_size') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-admin.button type="submit">Enregistrer</x-admin.button>
    </form>
</x-admin.layout>