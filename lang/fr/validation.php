<?php

return [

    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire quand :other vaut :value.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'file' => 'Le fichier :attribute ne doit pas dépasser :max kilo-octets.',
        'array' => 'Le champ :attribute ne doit pas contenir plus de :max éléments.',
    ],
    'image' => 'Le champ :attribute doit être une image.',
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'in' => 'La valeur sélectionnée pour :attribute n\'est pas valide.',
    'url' => 'Le format de l\'URL :attribute n\'est pas valide.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'exists' => 'La valeur sélectionnée pour :attribute n\'existe pas.',
    'array' => 'Le champ :attribute doit être un tableau.',

    'attributes' => [
        'title' => 'titre',
        'text' => 'texte',
        'content' => 'contenu',
        'author' => 'auteur',
        'label' => 'texte du bouton',
        'url' => 'lien',
        'style' => 'style',
        'new_tab' => 'nouvel onglet',
        'image' => 'image',
        'alt' => 'texte alternatif',
        'caption' => 'légende',
        'source_type' => 'source de la vidéo',
        'video_file' => 'fichier vidéo',
        'bg_color' => 'couleur de fond',
        'button_label' => 'texte du bouton',
        'button_url' => 'lien du bouton',
        'button_new_tab' => 'nouvel onglet',
        'pdf_source' => 'source du document',
        'pdf_document_id' => 'document existant',
        'pdf_title' => 'titre du document',
        'pdfs' => 'fichiers PDF',
        'pdfs.*' => 'fichier PDF',
    ],

];