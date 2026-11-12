import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * Jetons partages par les deux builds Tailwind (tailwind.config.js pour l'admin,
 * tailwind.public.config.js pour le public).
 *
 * Chaque couleur lit une variable CSS au format « R G B », ce qui garde les variantes
 * d'opacite (bg-cidst-primary/50) et permet de changer de theme sans recompiler.
 * Les variables sont emises par ThemeStyleBuilder (App\Services\Theme) ; leurs valeurs
 * par defaut sont dans resources/css/shared.css. ThemeTokensTest verifie l'accord.
 */
const rgb = (variable) => `rgb(var(${variable}) / <alpha-value>)`;

export const cidstColors = {
    // Anciens noms, retires avec le renommage des vues (lot 3d-3a).
    red: rgb('--color-primary-rgb'),
    ink: rgb('--color-secondary-rgb'),

    primary: rgb('--color-primary-rgb'),
    'primary-content': rgb('--color-primary-content-rgb'),
    secondary: rgb('--color-secondary-rgb'),
    bg: rgb('--color-bg-rgb'),
    surface: rgb('--color-surface-rgb'),
    text: rgb('--color-text-rgb'),
    muted: rgb('--color-muted-rgb'),
    border: rgb('--color-border-rgb'),
};

export const cidstFontFamily = {
    display: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
    sans: ['Inter', ...defaultTheme.fontFamily.sans],
    mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
};
