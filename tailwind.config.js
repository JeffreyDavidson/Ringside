import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/views/*.blade.php",
        "./resources/js/*.js",
    ],

    theme: {
        extend: {},
    },

    plugins: [forms],
};
