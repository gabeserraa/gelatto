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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    50: '#eef1f6',
                    100: '#dbe1eb',
                    400: '#5b6b85',
                    500: '#3d4d68',
                    700: '#16253d',
                    800: '#101f38',
                    900: '#0d1a2e',
                    950: '#0a1628',
                },
            },
            boxShadow: {
                card: '0 1px 4px 0 rgb(0 0 0 / 0.06)',
            },
            borderRadius: {
                card: '16px',
            },
        },
    },

    plugins: [forms],
};
