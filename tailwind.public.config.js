import forms from '@tailwindcss/forms';
import { cidstColors, cidstFontFamily } from './tailwind.tokens.js';

/**
 * Config Tailwind du site public : memes jetons que tailwind.config.js (fichier
 * tailwind.tokens.js), mais sans DaisyUI (admin uniquement) et sans scanner les vues
 * admin/auth/profile.
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
            fontFamily: cidstFontFamily,
            colors: {
                cidst: cidstColors,
            },
        },
    },
    plugins: [forms],
};
