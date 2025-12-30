import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class', // Habilita dark mode con clase .dark

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Colores del diseño Hando
                hando: {
                    // Modo Claro
                    'bg-light': '#F4F7FA',
                    'card-light': '#FFFFFF',
                    'text-light': '#1E293B',

                    // Modo Oscuro
                    'bg-dark': '#0F172A',
                    'card-dark': '#1E293B',
                    'text-dark': '#F8FAFC',

                    // Acciones
                    'primary': '#3B82F6',
                    'primary-hover': '#2563EB',
                    'danger': '#F43F5E',
                    'danger-hover': '#E11D48',

                    // Bordes
                    'border-light': '#D1D5DB',
                    'border-dark': '#334155',

                    // Grises
                    'gray-50': '#F8FAFC',
                    'gray-100': '#F1F5F9',
                    'gray-200': '#E2E8F0',
                    'gray-300': '#CBD5E1',
                    'gray-400': '#94A3B8',
                    'gray-500': '#64748B',
                    'gray-600': '#475569',
                    'gray-700': '#334155',
                    'gray-800': '#1E293B',
                    'gray-900': '#0F172A',
                },
            },
            borderRadius: {
                'hando': '8px',
            },
            boxShadow: {
                'hando-sm': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'hando': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                'hando-md': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            },
        },
    },

    plugins: [forms],
};
