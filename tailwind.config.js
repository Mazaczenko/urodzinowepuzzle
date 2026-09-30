import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Fredoka', ...defaultTheme.fontFamily.sans],
                display: ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                night: {
                    700: '#3b1d6e',
                    800: '#2a1455',
                    900: '#1a0f3c',
                    950: '#0d0822',
                },
                gold: {
                    200: '#fde9b0',
                    300: '#fbd77a',
                    400: '#f6c453',
                    500: '#e9a825',
                    600: '#c9870f',
                },
            },
            keyframes: {
                drift: {
                    '0%': { transform: 'translate3d(0, 0, 0)' },
                    '100%': { transform: 'translate3d(-40px, -110vh, 0)' },
                },
                twinkle: {
                    '0%, 100%': { opacity: '0.25' },
                    '50%': { opacity: '1' },
                },
                'pulse-soft': {
                    '0%, 100%': { opacity: '0.45', transform: 'scale(0.92)' },
                    '50%': { opacity: '1', transform: 'scale(1.08)' },
                },
            },
            animation: {
                drift: 'drift linear infinite',
                twinkle: 'twinkle ease-in-out infinite',
                'pulse-soft': 'pulse-soft 1.8s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
