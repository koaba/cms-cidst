import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Config Tailwind du site public : memes jetons que tailwind.config.js, mais
 * sans DaisyUI (admin uniquement) et sans scanner les vues admin/auth/profile.
 * Toute classe publique doit figurer en toutes lettres dans ces dossiers.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './resources/views/public/**/*.blade.php',
        './resources/views/pages/**/*.blade.php',
        './resources/views/partials/**/*.blade.php',
        './resources/views/vendor/pagination/*.blade.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/welcome.blade.php',
        './resources/views/components/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                display: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                cidst: {
                    red: 'rgb(var(--color-primary-rgb) / <alpha-value>)',
                    ink: 'rgb(var(--color-secondary-rgb) / <alpha-value>)',
                    surface: '#FFFFFF',
                    bg: '#F1F2F4',
                    border: '#D8DADE',
                    muted: '#6B7078',
                },
            },
        },
    },
    plugins: [forms],
};
