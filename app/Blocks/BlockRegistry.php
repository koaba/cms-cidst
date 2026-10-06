<?php

namespace App\Blocks;

use App\Blocks\Rules\AccordeonItemRules;
use App\Blocks\Rules\AccordeonRules;
use App\Blocks\Rules\BanniereHeroRules;
use App\Blocks\Rules\BoutonRules;
use App\Blocks\Rules\CitationRules;
use App\Blocks\Rules\ColonnesRules;
use App\Blocks\Rules\GalerieRules;
use App\Blocks\Rules\ImageRules;
use App\Blocks\Rules\PdfRules;
use App\Blocks\Rules\SectionFondRules;
use App\Blocks\Rules\SeparateurRules;
use App\Blocks\Rules\TexteRules;
use App\Blocks\Rules\VideoRules;
use App\Contracts\BlockRules;

/**
 * Registre des types de blocs : type => classe de règles.
 * Ajouter un type = une classe de règles + une ligne ici.
 */
class BlockRegistry
{
    /** @var array<string, class-string<BlockRules>> */
    private const RULES = [
        'texte' => TexteRules::class,
        'separateur' => SeparateurRules::class,
        'citation' => CitationRules::class,
        'bouton' => BoutonRules::class,
        'banniere_hero' => BanniereHeroRules::class,
        'image' => ImageRules::class,
        'video' => VideoRules::class,
        'section_fond' => SectionFondRules::class,
        'galerie' => GalerieRules::class,
        'pdf' => PdfRules::class,
        'colonnes' => ColonnesRules::class,
        'accordeon' => AccordeonRules::class,
        'accordeon_item' => AccordeonItemRules::class,
    ];

    public static function rulesFor(string $type): BlockRules
    {
        $class = self::RULES[$type]
            ?? abort(404, "Type de bloc « {$type} » non implémenté.");

        return new $class;
    }

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::RULES);
    }
}
