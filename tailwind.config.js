import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            // Tipografía de la marca (cargada desde Google Fonts en app.blade.php).
            fontFamily: {
                // Texto de toda la app.
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                // Títulos: font-display.
                display: [
                    'Bricolage Grotesque',
                    'Manrope',
                    ...defaultTheme.fontFamily.sans,
                ],
                // Acento manuscrito (ej. "conecta" en la landing): font-acento.
                acento: ['"Caveat Brush"', 'cursive'],
            },
            // Paleta de la marca (mockup). Uso: bg-marca-azul, text-marca-ambar, etc.
            colors: {
                marca: {
                    'azul-claro': '#3BA8F0',
                    azul: '#1E88E5',
                    'azul-profundo': '#0B6AAE',
                    ambar: '#F0A73C',
                    dorado: '#B7791F',
                    // Gris azulado (KAN-349): con el casi blanco anterior
                    // (#F4F8FC) las tarjetas blancas no se separaban del fondo.
                    // Si se cambia, cambiarlo también en app.blade.php.
                    fondo: '#E8EEF5',
                    tinta: '#0F2540',
                },
            },
        },
    },

    plugins: [forms],
};
