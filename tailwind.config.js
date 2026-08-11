import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
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
                    red: 'var(--color-primary, #C41E2A)',
                    ink: 'var(--color-secondary, #17181A)',
                    surface: '#FFFFFF',
                    bg: '#F1F2F4',
                    border: '#D8DADE',
                    muted: '#6B7078',
                },
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