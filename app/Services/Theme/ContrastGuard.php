<?php

namespace App\Services\Theme;

/**
 * Garde-fou de lisibilité des couleurs choisies dans les réglages (politique A).
 *
 * BLOQUE : le texte principal sous 4,5 sur le fond ou sur la surface.
 * AVERTIT seulement : texte secondaire (4,5), couleur primaire sur la surface (3),
 * bordures qui se confondent avec la surface (1,2). Le texte sur primaire n'est jamais
 * saisi : il est calculé par ContrastChecker::readableTextOn().
 *
 * Le ratio vient du seul ContrastChecker, comme pour le thème et les tests.
 */
final class ContrastGuard
{
    /**
     * Premier plan, arrière-plan, minimum, bloquant, libellé.
     *
     * @var list<array{0: string, 1: string, 2: float, 3: bool, 4: string}>
     */
    private const PAIRS = [
        ['text', 'bg', ContrastChecker::MINIMUM, true, 'Texte principal sur le fond'],
        ['text', 'surface', ContrastChecker::MINIMUM, true, 'Texte principal sur la surface'],
        ['muted', 'bg', ContrastChecker::MINIMUM, false, 'Texte secondaire sur le fond'],
        ['muted', 'surface', ContrastChecker::MINIMUM, false, 'Texte secondaire sur la surface'],
        ['primary', 'surface', 3.0, false, 'Couleur primaire sur la surface'],
        ['border', 'surface', 1.2, false, 'Bordures sur la surface'],
    ];

    public function __construct(private readonly ContrastChecker $checker = new ContrastChecker) {}

    /**
     * @return list<array{label: string, foreground: string, background: string, ratio: float, minimum: float, blocking: bool, passes: bool}>
     */
    public function report(ThemePalette $palette): array
    {
        return array_map(function (array $pair) use ($palette): array {
            [$foreground, $background, $minimum, $blocking, $label] = $pair;
            $foregroundColor = $palette->color($foreground);
            $backgroundColor = $palette->color($background);

            return [
                'label' => $label,
                'foreground' => $foreground,
                'background' => $background,
                'ratio' => $this->checker->ratio($foregroundColor, $backgroundColor),
                'minimum' => $minimum,
                'blocking' => $blocking,
                'passes' => $this->checker->passes($foregroundColor, $backgroundColor, $minimum),
            ];
        }, self::PAIRS);
    }

    /**
     * Messages des paires qui empêchent l'enregistrement.
     *
     * @return list<string>
     */
    public function blockers(ThemePalette $palette): array
    {
        return $this->messages($palette, true);
    }

    /**
     * Messages des paires à surveiller, sans bloquer l'enregistrement.
     *
     * @return list<string>
     */
    public function warnings(ThemePalette $palette): array
    {
        return $this->messages($palette, false);
    }

    /** Nombre à la française, tronqué à deux décimales (4,49 ne devient pas 4,5), sans zéros inutiles. */
    public static function format(float $value): string
    {
        $truncated = floor(round($value * 100, 4)) / 100;

        return rtrim(rtrim(number_format($truncated, 2, ',', ''), '0'), ',');
    }

    /** @return list<string> */
    private function messages(ThemePalette $palette, bool $blocking): array
    {
        $messages = [];

        foreach ($this->report($palette) as $row) {
            if ($row['blocking'] === $blocking && ! $row['passes']) {
                $messages[] = sprintf(
                    '%s : contraste %s:1, minimum %s:1.',
                    $row['label'],
                    self::format($row['ratio']),
                    self::format($row['minimum']),
                );
            }
        }

        return $messages;
    }
}
