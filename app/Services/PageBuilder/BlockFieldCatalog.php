<?php

namespace App\Services\PageBuilder;

use InvalidArgumentException;

/**
 * Métadonnées de formulaire des types de blocs editables dans le Page Builder.
 *
 * La validation reste dans les classes App\Blocks\Rules\* : ce catalogue ne
 * decrit que l'affichage. Un test de coherence impose que les cles des champs
 * soient celles des regles (hors champs media), pour eviter toute derive.
 * Les cles sont celles deja stockees dans page_blocks.data.
 */
final class BlockFieldCatalog
{
    private const DEFINITIONS = [
        'texte' => [
            'creatable' => true,
            'defaults' => ['title' => '', 'content' => ''],
            'fields' => [
                ['key' => 'title', 'type' => 'text', 'label' => 'Titre', 'max' => 255],
                ['key' => 'content', 'type' => 'textarea', 'label' => 'Contenu', 'required' => true],
            ],
        ],
        'banniere_hero' => [
            'creatable' => true,
            'media' => true,
            'defaults' => [
                'titre' => '', 'sous_titre' => '', 'bouton_texte' => '', 'bouton_url' => '',
                'overlay_opacity' => 40, 'alt' => '',
            ],
            'fields' => [
                ['key' => 'titre', 'type' => 'text', 'label' => 'Titre', 'required' => true, 'max' => 255],
                ['key' => 'sous_titre', 'type' => 'text', 'label' => 'Sous-titre', 'max' => 500],
                ['key' => 'bouton_texte', 'type' => 'text', 'label' => 'Texte du bouton', 'max' => 100],
                ['key' => 'bouton_url', 'type' => 'text', 'label' => 'Lien du bouton', 'help' => 'URL complète, par exemple https://exemple.org/contact'],
                ['key' => 'overlay_opacity', 'type' => 'number', 'label' => 'Opacité du voile (%)', 'min' => 0, 'max' => 100],
                ['key' => 'alt', 'type' => 'text', 'label' => 'Texte alternatif de l\'image', 'max' => 255, 'help' => 'Décrit l\'image pour les lecteurs d\'écran et le référencement.'],
            ],
        ],
        'image' => [
            'creatable' => true,
            'media' => true,
            'defaults' => ['alt' => '', 'caption' => ''],
            'fields' => [
                ['key' => 'alt', 'type' => 'text', 'label' => 'Texte alternatif', 'max' => 255, 'help' => "D\u{00E9}crit l'image pour les lecteurs d'\u{00E9}cran et le r\u{00E9}f\u{00E9}rencement."],
                ['key' => 'caption', 'type' => 'text', 'label' => "L\u{00E9}gende", 'max' => 255],
            ],
        ],
        'bouton' => [
            'creatable' => true,
            'defaults' => ['label' => '', 'url' => '', 'style' => 'primaire', 'new_tab' => false],
            'fields' => [
                ['key' => 'label', 'type' => 'text', 'label' => 'Texte du bouton', 'required' => true, 'max' => 100],
                ['key' => 'url', 'type' => 'text', 'label' => 'Lien', 'required' => true, 'max' => 255, 'help' => 'Lien interne (/contact) ou externe (https://...).'],
                ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => [
                    ['value' => 'primaire', 'label' => 'Primaire'],
                    ['value' => 'secondaire', 'label' => 'Secondaire'],
                    ['value' => 'outline', 'label' => 'Contour'],
                ]],
                ['key' => 'new_tab', 'type' => 'checkbox', 'label' => 'Ouvrir dans un nouvel onglet'],
            ],
        ],
        'citation' => [
            'creatable' => true,
            'defaults' => ['content' => '', 'author' => ''],
            'fields' => [
                ['key' => 'content', 'type' => 'textarea', 'label' => 'Citation', 'required' => true],
                ['key' => 'author', 'type' => 'text', 'label' => 'Auteur', 'max' => 255],
            ],
        ],
        'separateur' => [
            'creatable' => true,
            'defaults' => ['style' => 'fin'],
            'fields' => [
                ['key' => 'style', 'type' => 'select', 'label' => 'Épaisseur', 'options' => [
                    ['value' => 'fin', 'label' => 'Fin'],
                    ['value' => 'epais', 'label' => 'Épais'],
                ]],
            ],
        ],
    ];

    /** @return array<string, array{creatable: bool, defaults: array, fields: list<array>, note?: string}> */
    public static function all(): array
    {
        return self::DEFINITIONS;
    }

    /** @return array{creatable: bool, defaults: array, fields: list<array>, note?: string} */
    public static function for(string $type): array
    {
        return self::DEFINITIONS[$type]
            ?? throw new InvalidArgumentException("Type « {$type} » absent du catalogue du Page Builder.");
    }
}
