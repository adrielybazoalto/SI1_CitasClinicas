import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['Figtree', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#FAF5FF',
                    100: '#F3E8FF',
                    200: '#E9D5FF',
                    300: '#D8B4FE',
                    400: '#C084FC',
                    500: '#A855F7',
                    600: '#9333EA',
                    700: '#7E22CE',
                    800: '#6B21A8',
                    900: '#581C87',
                    950: '#3B0764',
                },
                health: {
                    dark: '#4C1D95',
                    primary: '#6B21A8',
                    hover: '#581C87',
                    light: '#F3E8FF',
                    surface: '#FAF5FF',
                    accent: '#8B5CF6',
                },
            },
            boxShadow: {
                'brand-sm': '0 2px 8px -1px rgba(107, 33, 168, 0.08)',
                'brand': '0 8px 24px -4px rgba(107, 33, 168, 0.12)',
                'brand-lg': '0 16px 32px -6px rgba(107, 33, 168, 0.18)',
            },
        },
    },

    plugins: [forms],
};
