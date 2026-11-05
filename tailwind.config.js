import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Paleta ciruela de Vida Salud. Redefine `purple` para que todas las vistas
 * (que usan purple-*) cambien de color sin tocarlas; `brand` es un alias.
 */
const ciruela = {
    50: '#FBF6FB',
    100: '#F4E9F4',
    200: '#E8D2E8',
    300: '#D5AFD6',
    400: '#BA82BC',
    500: '#9B5A9F',
    600: '#7F3F84',
    700: '#6A2E6F',
    800: '#5A215E', // botón principal
    900: '#4A1A4E',
    950: '#2E0B31',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './config/menu.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                purple: ciruela,
                brand: ciruela,
                health: {
                    dark: '#4A1A4E',
                    primary: '#5A215E',
                    hover: '#4A1A4E',
                    light: '#F4E9F4',
                    surface: '#FBF6FB',
                    accent: '#9B5A9F',
                },
            },
            boxShadow: {
                // Las vistas usan shadow-xs (nombre de Tailwind 4); en Tailwind 3 no existe.
                'xs': '0 1px 2px 0 rgba(90, 33, 94, 0.05)',
                'brand-sm': '0 2px 8px -1px rgba(90, 33, 94, 0.08)',
                'brand': '0 8px 24px -4px rgba(90, 33, 94, 0.12)',
                'brand-lg': '0 16px 32px -6px rgba(90, 33, 94, 0.18)',
            },
        },
    },

    plugins: [forms],
};
