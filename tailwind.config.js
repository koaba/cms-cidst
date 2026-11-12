import forms from '@tailwindcss/forms';
import daisyui from 'daisyui';
import { cidstColors, cidstFontFamily } from './tailwind.tokens.js';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    safelist: [
        'bg-blue-600', 'text-white',
    ],
    theme: {
        extend: {
            fontFamily: cidstFontFamily,
            colors: {
                admin: {
                    bg: 'var(--admin-bg)',
                    nav: 'var(--admin-nav)',
                    hover: 'var(--admin-hover)',
                    text: 'var(--admin-text)',
                },
                cidst: cidstColors,
            },
        },
    },
    plugins: [
        forms,
        daisyui,
    ],
    daisyui: {
        themes: [
            {
                cidst: {
                    primary: '#C41E2A',
                    secondary: '#17181A',
                    accent: '#C41E2A',
                    neutral: '#17181A',
                    'base-100': '#FFFFFF',
                    'base-200': '#F1F2F4',
                    'base-300': '#D8DADE',
                    info: '#3ABFF8',
                    success: '#36D399',
                    warning: '#FBBD23',
                    error: '#F87272',
                },
            },
        ],
        darkTheme: false,
    },
}