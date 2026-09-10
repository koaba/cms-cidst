{{--
    Composant : liste réordonnable (drag & drop) de médias existants, avec suppression.

    Factorise le motif dupliqué 3 fois dans edit.blade.php :
    - galerie d'images
    - documents PDF
    - images d'un diaporama

    Le drag & drop / la persistance de l'ordre restent gérés par media-reorder.js
    (data-media-reorder / data-media-id) : ce composant ne fait QUE le rendu HTML.

    Props :
    - items          : Collection de médias à afficher (obligatoire)
    - variant        : 'image' (défaut) ou 'pdf' — change le rendu de chaque item
    - modelType      : 'article' | 'diaporama' | ... (valeur de data-model-type)
    - modelId        : id du modèle (valeur de data-model-id)
    - deleteField    : nom du champ checkbox de suppression, ex. 'delete_images[]'
                       ou 'diaporamas[0][delete_images][]'
    - widthClass     : classe Tailwind littérale de largeur (variant='image' uniquement),
                       ex. "w-24" (galerie) ou "w-20" (diaporama). Appliquée au label.
    - heightClass    : classe Tailwind littérale de hauteur pour l'image elle-même,
                       ex. "h-24" ou "h-20". Passer des chaînes littérales à l'appel
                       (pas de valeur dynamique) pour rester compatible avec le scan
                       JIT de Tailwind.
--}}
@props([
    'items',
    'variant' => 'image',
    'modelType',
    'modelId',
    'deleteField',
    'widthClass' => 'w-24',
    'heightClass' => 'h-24',
])

@if ($items->isNotEmpty())
    <div class="flex flex-wrap gap-3 mb-3"
         data-media-reorder
         data-reorder-url="{{ route('admin.media.reorder') }}"
         data-model-type="{{ $modelType }}"
         data-model-id="{{ $modelId }}">
        @foreach ($items as $media)
            @if ($variant === 'pdf')
                <label class="flex items-center gap-2 text-sm border rounded px-3 py-2 bg-gray-50 cursor-move" draggable="true" data-media-id="{{ $media->id }}">
                    @if ($media->thumbnail_path)
                        <img src="{{ Storage::url($media->thumbnail_path) }}" class="w-8 h-10 object-cover rounded border" draggable="false">
                    @else
                        <span aria-hidden="true">&#128196;</span>
                    @endif
                    <span class="flex-1">{{ $media->original_name }}</span>
                    <span class="flex items-center gap-1 text-xs">
                        <input type="checkbox" name="{{ $deleteField }}" value="{{ $media->id }}">
                        Supprimer
                    </span>
                </label>
            @else
                <label class="block {{ $widthClass }} cursor-move" draggable="true" data-media-id="{{ $media->id }}">
                    <img src="{{ Storage::url($media->path) }}" class="{{ $widthClass }} {{ $heightClass }} object-cover rounded border" draggable="false">
                    <span class="flex items-center gap-1 text-xs mt-1">
                        <input type="checkbox" name="{{ $deleteField }}" value="{{ $media->id }}">
                        Supprimer
                    </span>
                </label>
            @endif
        @endforeach
    </div>
@endif