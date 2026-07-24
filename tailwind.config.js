import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Colores de acento de SubDepartment::COLORES (app/Domain/Organization/Models/SubDepartment.php).
// Mantener esta lista en sync con las claves de esa constante PHP.
const SUBDEPARTMENT_COLORS = [
    'slate', 'gray', 'zinc', 'neutral', 'stone',
    'red', 'orange', 'amber', 'yellow', 'lime', 'green',
    'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo',
    'violet', 'purple', 'fuchsia', 'pink', 'rose',
];

// Esas clases solo existen como strings dentro de un archivo PHP (el modelo),
// fuera del `content` que Tailwind escanea (solo mira .blade.php): sin este
// safelist, la mayoria de los colores no se compilarian y el swatch/badge/
// gradiente de ese subdepartamento se veria sin color.
const subdepartmentSafelist = SUBDEPARTMENT_COLORS.flatMap((c) => [
    `bg-${c}-100`, `text-${c}-700`,
    `dark:bg-${c}-500/15`, `dark:text-${c}-300`,
    `from-${c}-600`, `to-${c}-700`,
    `text-${c}-500`, `dark:text-${c}-400`,
]);

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: subdepartmentSafelist,

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
