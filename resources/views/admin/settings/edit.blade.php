<x-admin.layout title="Réglages du site">
    <h1 class="text-2xl font-bold mb-6">Réglages du site — Page d'accueil</h1>

   @if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 p-3 rounded mb-4">{{ session('error') }}</div>
@endif
@if(session('warnings'))
    <div class="alert alert-warning mb-4" role="status">
        <div>
            <p class="font-medium">Couleurs enregistrées, avec des points à surveiller :</p>
            <ul class="list-disc pl-5 text-sm">
                @foreach (session('warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium mb-1">Logo du site</label>
            @if($settings->logo_path)
                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo actuel" class="h-16 mb-2">
            @endif
            <input type="file" name="logo" accept="image/jpeg,image/png" class="w-full border rounded p-2">
            <p class="text-xs text-base-content/60 mt-1">PNG avec fond transparent recommandé (un JPEG garde un fond plein). Converti en WebP à l'envoi. Laisse vide pour conserver le logo actuel.</p>
            @error('logo') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="logo_height" class="block font-medium mb-1">Hauteur du logo dans le menu (px)</label>
            <input type="number" id="logo_height" name="logo_height" min="24" max="72" step="1"
                   value="{{ old('logo_height', $settings->logo_height) }}" class="w-32 border rounded p-2">
            <p class="text-xs text-base-content/60 mt-1">Entre 24 et 72 px (48 par défaut). La largeur suit les proportions du logo.</p>
            @error('logo_height') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <fieldset>
            <legend class="block font-medium mb-1">Thème du site</legend>
            <div class="space-y-2">
                @foreach (config('themes.themes') as $key => $theme)
                    <label class="flex items-center gap-3 border rounded p-3 cursor-pointer">
                        <input type="radio" name="theme" value="{{ $key }}" @checked(old('theme', $settings->theme ?? config('themes.default')) === $key)>
                        <span class="font-medium">{{ $theme['label'] }}</span>
                        @isset($theme['palette'])
                            <span class="flex gap-1 ml-auto" aria-hidden="true">
                                @foreach (['primary', 'secondary', 'accent'] as $role)
                                    <span class="w-5 h-5 rounded-full border" style="background: {{ $theme['palette'][$role] }}"></span>
                                @endforeach
                            </span>
                        @endisset
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-base-content/60 mt-1">Les couleurs personnalisées ci-dessous passent après la palette du thème choisi.</p>
            @error('theme') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset class="space-y-3">
            <legend class="block font-medium mb-1">Couleurs du site</legend>
            <p class="text-xs text-base-content/60">
                Sept rôles de couleur. Le texte posé sur la couleur primaire est calculé automatiquement pour rester lisible.
                Un texte principal sous 4,5:1 de contraste sur le fond ou la surface bloque l'enregistrement ; les autres écarts sont seulement signalés.
            </p>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (\App\Services\Theme\ThemeRoles::keys() as $role)
                    @php($color = old('theme_colors.'.$role, $palette->color($role)))
                    <div x-data>
                        <label for="theme_colors_{{ $role }}" class="block font-medium mb-1">{{ \App\Services\Theme\ThemeRoles::label($role) }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" value="{{ $color }}" x-ref="picker"
                                   x-on:input="$refs.hex.value = $event.target.value.toUpperCase()"
                                   aria-label="Sélecteur de couleur : {{ \App\Services\Theme\ThemeRoles::label($role) }}"
                                   class="h-11 w-14 border rounded p-1 cursor-pointer">
                            <input type="text" id="theme_colors_{{ $role }}" name="theme_colors[{{ $role }}]" value="{{ $color }}" x-ref="hex"
                                   x-on:input="if (/^#[0-9A-Fa-f]{6}$/.test($event.target.value)) $refs.picker.value = $event.target.value"
                                   maxlength="7" pattern="#[0-9A-Fa-f]{6}" autocomplete="off" spellcheck="false"
                                   class="w-32 h-11 border rounded p-2 font-mono uppercase">
                        </div>
                        @foreach ($errors->get('theme_colors.'.$role) as $message)
                            <p class="text-red-600 text-sm" role="alert">{{ $message }}</p>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @error('theme_colors')
                <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror

            <div class="border rounded p-3 space-y-1">
                <p class="font-medium">Lisibilité des couleurs enregistrées</p>
                <ul class="text-sm space-y-0.5">
                    @foreach ($report as $row)
                        <li>
                            {{ $row['label'] }} : {{ \App\Services\Theme\ContrastGuard::format($row['ratio']) }}:1
                            (minimum {{ \App\Services\Theme\ContrastGuard::format($row['minimum']) }}:1) —
                            @if ($row['passes'])
                                correct
                            @else
                                {{ $row['blocking'] ? 'insuffisant, enregistrement bloqué' : 'à surveiller' }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <button type="submit" form="reset-colors-form" class="btn btn-outline btn-sm">Réinitialiser les couleurs</button>
        </fieldset>

        <div>
            <label class="block font-medium mb-1">Direction du bandeau d'actualités</label>
            <select name="news_ticker_direction" class="w-full border rounded p-2">
                <option value="horizontal" {{ old('news_ticker_direction', $settings->news_ticker_direction) === 'horizontal' ? 'selected' : '' }}>Horizontal (défilement latéral)</option>
                <option value="vertical" {{ old('news_ticker_direction', $settings->news_ticker_direction) === 'vertical' ? 'selected' : '' }}>Vertical (défilement du bas vers le haut)</option>
            </select>
                      @error('news_ticker_direction') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>
<div>
    <label class="flex items-center gap-2 font-medium mb-1">
        <input
            type="checkbox"
            name="video_watermark_default_enabled"
            value="1"
            class="h-4 w-4"
            {{ old('video_watermark_default_enabled', $settings->video_watermark_default_enabled) ? 'checked' : '' }}
        >
        Activer le filigrane par défaut pour les nouvelles vidéos
    </label>
        @error('video_watermark_default_enabled') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
</div>
<div>
    <label class="flex items-center gap-2 font-medium mb-1">
        <input
            type="checkbox"
            name="image_watermark_default_enabled"
            value="1"
            class="h-4 w-4"
            {{ old('image_watermark_default_enabled', $settings->image_watermark_default_enabled) ? 'checked' : '' }}
        >
        Activer le filigrane par défaut pour les nouvelles images
    </label>
    @error('image_watermark_default_enabled') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
</div>

<div>
    <label class="flex items-center gap-2 font-medium mb-1">
        <input
            type="checkbox"
            name="pdf_watermark_default_enabled"
            value="1"
            class="h-4 w-4"
            {{ old('pdf_watermark_default_enabled', $settings->pdf_watermark_default_enabled) ? 'checked' : '' }}
        >
        Activer le filigrane par défaut pour les nouveaux PDF
    </label>
    @error('pdf_watermark_default_enabled') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
</div>

<div>
    <label class="flex items-center gap-2 font-medium mb-1">
        <input
            type="checkbox"
            name="diaporama_watermark_default_enabled"
            value="1"
            class="h-4 w-4"
            {{ old('diaporama_watermark_default_enabled', $settings->diaporama_watermark_default_enabled) ? 'checked' : '' }}
        >
        Activer le filigrane par défaut pour les nouveaux diaporamas
    </label>
    @error('diaporama_watermark_default_enabled') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
</div>
        <div>
            <label class="block font-medium mb-1">Eyebrow (petit label au-dessus du titre)</label>
            <input type="text" name="hero_eyebrow" value="{{ old('hero_eyebrow', $settings->hero_eyebrow) }}"
                   class="w-full border rounded p-2 mb-2">

            <label class="block text-sm text-base-content/70 mb-1">Taille de l'eyebrow</label>
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
<div>
    <label class="block font-medium mb-1">URL Facebook</label>
    <input type="url" name="facebook_url" value="{{ old('facebook_url', $settings->facebook_url) }}"
           placeholder="https://facebook.com/votrepage"
           class="w-full border rounded p-2">
    @error('facebook_url') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
</div>
<div>
    <label class="block font-medium mb-1">Titre de la page Documents PDF</label>
    <input type="text" name="pdf_documents_title" value="{{ old('pdf_documents_title', $settings->pdf_documents_title) }}"
           placeholder="Documents et ressources"
           class="w-full border rounded p-2">
    @error('pdf_documents_title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
</div>
<div>
    <label class="block font-medium mb-1">Contenu introductif de la page Documents PDF</label>
    <textarea name="pdf_documents_content" rows="4"
              placeholder="Texte affiché en haut de la page listant les documents..."
              class="w-full border rounded p-2">{{ old('pdf_documents_content', $settings->pdf_documents_content) }}</textarea>
    @error('pdf_documents_content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
</div>
        <div><label class="block font-medium mb-1">Code de suppression</label><input type="password" name="delete_code" autocomplete="new-password" minlength="8" maxlength="50" class="w-full border rounded p-2"><p class="text-sm opacity-70">Laisser vide pour ne pas changer. Demand&eacute; aux utilisateurs Publication pour supprimer.</p>@error('delete_code') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror</div>
<fieldset class="border rounded p-4 space-y-3">
    <legend class="font-medium px-1">Formulaire de contact : images jointes</legend>
    <label class="flex items-center gap-2 font-medium">
        <input type="checkbox" name="contact_images_enabled" value="1" class="h-4 w-4" {{ old('contact_images_enabled', $settings->contact_images_enabled) ? 'checked' : '' }}>
        Autoriser les visiteurs à joindre des images
    </label>
    <div>
        <label for="contact_images_max_mb" class="block font-medium mb-1">Taille maximale par image (Mo)</label>
        <input id="contact_images_max_mb" type="number" min="1" max="10" name="contact_images_max_mb" value="{{ old('contact_images_max_mb', $settings->contact_images_max_mb) }}" class="w-32 border rounded p-2">
        @error('contact_images_max_mb') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>
    <p class="text-sm opacity-70">Les images sont réencodées et rangées hors du site public. Les PDF ne sont pas acceptés : les visiteurs utilisent le lien de partage.</p>
</fieldset>
<fieldset class="border rounded p-4 space-y-3">
    <legend class="font-medium px-1">Formulaire de contact : adresse pour les documents</legend>
    <div>
        <label for="contact_public_email" class="block font-medium mb-1">Adresse e-mail affichée sur la page Contact</label>
        <input id="contact_public_email" type="email" name="contact_public_email" maxlength="255" value="{{ old('contact_public_email', $settings->contact_public_email) }}" class="w-full border rounded p-2">
        @error('contact_public_email') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        <p class="text-sm opacity-70">Les visiteurs peuvent y envoyer un PDF ou un document. Laisser vide pour ne rien afficher.</p>
    </div>
</fieldset>
<fieldset class="border rounded p-4 space-y-4">
    <legend class="font-medium px-1">Pied de page public</legend>
    {{-- Apres une erreur de validation, une case decochee reste decochee (old() seul la recocherait). --}}
    @php($footerChecked = fn (string $field): bool => session()->hasOldInput() ? (bool) old($field) : (bool) ($settings->{$field} ?? true))
    <label class="flex items-center gap-2 font-medium">
        <input type="checkbox" name="show_footer" value="1" class="h-4 w-4" {{ $footerChecked('show_footer') ? 'checked' : '' }}>
        Afficher le pied de page
    </label>
    <label class="flex items-center gap-2 font-medium">
        <input type="checkbox" name="footer_show_contacts" value="1" class="h-4 w-4" {{ $footerChecked('footer_show_contacts') ? 'checked' : '' }}>
        Afficher l'e-mail public et le lien Facebook
    </label>
    <div>
        <label for="footer_description" class="block font-medium mb-1">Courte description</label>
        <input id="footer_description" type="text" name="footer_description" maxlength="255" value="{{ old('footer_description', $settings->footer_description) }}" class="w-full border rounded p-2">
        @error('footer_description') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        <p class="text-sm opacity-70">Affichée sous le logo. Laisser vide pour ne rien afficher.</p>
    </div>
    <div>
        <label for="footer_copyright" class="block font-medium mb-1">Texte du copyright</label>
        <input id="footer_copyright" type="text" name="footer_copyright" maxlength="255" value="{{ old('footer_copyright', $settings->footer_copyright) }}" placeholder="© {annee} {site}" class="w-full border rounded p-2">
        @error('footer_copyright') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        <p class="text-sm opacity-70">{annee} devient l'année en cours et {site} le nom du site. Laisser vide pour « © année Nom du site ».</p>
    </div>
</fieldset>
<div class="flex items-center gap-3">
            <x-admin.button type="submit">Enregistrer</x-admin.button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>

    <form id="reset-colors-form" action="{{ route('admin.settings.colors.reset') }}" method="POST"
          onsubmit="return confirm('Remettre toutes les couleurs aux valeurs par défaut ?')">
        @csrf
        @method('DELETE')
    </form>
</x-admin.layout>