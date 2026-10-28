import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                /*
                 * Modo oscuro estilo TailAdmin: solo se usa con dark: (el modo claro sigue en zinc).
                 * Grises de TailAdmin; 900 y 950 van por función, no por nombre:
                 *   950 = fondo de la página (gray-900 de TailAdmin)
                 *   900 = tarjetas, encabezados y celdas fijas (gray-900 + 3 % de blanco, opaco)
                 *   800 = bordes, encabezados de tabla y hover (gray-800 de TailAdmin)
                 */
                'ta-gray': {
                    50: '#f9fafb',
                    100: '#f2f4f7',
                    200: '#e4e7ec',
                    300: '#d0d5dd',
                    400: '#98a2b3',
                    500: '#667085',
                    600: '#475467',
                    700: '#344054',
                    800: '#1d2939',
                    900: '#171f2f',
                    950: '#101828',
                },
            },
        },
    },

    plugins: [forms, typography],
};
