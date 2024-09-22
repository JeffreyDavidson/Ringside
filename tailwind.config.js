import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/views/*.blade.php",
        "./resources/js/*.js",
        "./vendor/rappasoft/laravel-livewire-tables/resources/views/*.blade.php",
        "./vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php",
        "./app/Livewire/*.php",
        "./app/Livewire/**/*.php",
    ],

    theme: {
        extend: {},
    },

    plugins: [forms],
};
