import {defineConfig} from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

export default defineConfig({
    base: './',
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
            ],
            refresh: true,
            postcss: [
                tailwindcss(),
                autoprefixer(),
            ]
        }),
    ]
});
