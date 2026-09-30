import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Theme colours are CSS variables (see resources/css/app.css), so one class works in every theme.
const token = (name) => `rgb(var(--color-${name}) / <alpha-value>)`;

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
                sans: ['var(--font-sans)', ...defaultTheme.fontFamily.sans],
                display: ['var(--font-display)', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // The background shades of the theme.
                night: {
                    700: token('night-700'),
                    800: token('night-800'),
                    900: token('night-900'),
                    950: token('night-950'),
                },
                // The accent of the theme: headings, buttons, progress.
                gold: {
                    200: token('gold-200'),
                    300: token('gold-300'),
                    400: token('gold-400'),
                    500: token('gold-500'),
                    600: token('gold-600'),
                },
                // The two soft glows in the background.
                glow: {
                    a: token('glow-a'),
                    b: token('glow-b'),
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
