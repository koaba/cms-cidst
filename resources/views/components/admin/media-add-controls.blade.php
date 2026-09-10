{{--
    Composant : bloc "uploader de nouveaux fichiers" + "choisir depuis la médiathèque".

    Factorise le motif dupliqué entre la galerie et les diaporamas dans
    edit.blade.php / create.blade.php.

    Volontairement PAS utilisé pour les PDF : leur uploader a un comportement
    spécifique (génération de miniatures côté client via pdf-thumbnail.js,
    input caché pour les données de miniature) qui n'a rien de commun ici.
    Cf. décision similaire prise pour MediaSyncService : ne pas forcer un
    point commun qui n'existe pas réellement.

    Props :
    - previewContainerId : id du div où les chips de fichiers sélectionnés s'affichent
    - uploadFieldName     : name de l'input file, ex. 'images[]' ou 'diaporamas[0][images][]'
    - pickFieldName       : name du champ caché ajouté par la médiathèque,
                            ex. 'existing_media[]' ou 'diaporamas[0][existing_media][]'
    - accept              : attribut accept de l'input file (défaut 'image/*')
    - label               : texte du bouton d'upload (défaut 'Uploader des images')
--}}
@props([
    'previewContainerId',
    'uploadFieldName',
    'pickFieldName',
    'accept' => 'image/*',
    'label' => 'Uploader des images',
])

<div id="{{ $previewContainerId }}" class="flex flex-wrap gap-2 mb-2"></div>

<div class="flex gap-2">
    <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100">
        + {{ $label }}
        <input type="file" name="{{ $uploadFieldName }}" accept="{{ $accept }}" multiple class="hidden"
               onchange="ArticleForm.previewNewUploads(this, '{{ $previewContainerId }}')">
    </label>
    <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100"
            onclick="ArticleForm.pickExistingMedia('{{ $previewContainerId }}', '{{ $pickFieldName }}')">
        Choisir depuis la médiathèque
    </button>
</div>