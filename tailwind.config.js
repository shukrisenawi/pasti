import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                'base-100': '#fcfffc',
                'base-200': '#edf6ef',
                'base-300': '#d8e8db',
                'base-content': '#163022',
                primary: '#1f7a46',
                'primary-dark': '#175b34',
                'primary-content': '#f6fff8',
                secondary: '#2f855a',
                accent: '#7aa874',
                success: '#15803d',
                info: '#10b981',
                'info-content': '#064e3b',
                warning: '#b45309',
                error: '#b91c1c',
            },
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 18px 40px -26px rgba(22, 48, 34, 0.35)',
            },
        },
    },

    plugins: [forms],
};
