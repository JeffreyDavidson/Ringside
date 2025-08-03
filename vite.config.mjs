import {defineConfig} from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';
import { visualizer } from 'rollup-plugin-visualizer';

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
        // Bundle analyzer - only include in production builds with ANALYZE=true
        process.env.ANALYZE && visualizer({
            filename: 'public/build/bundle-analysis.html',
            open: true,
            gzipSize: true,
            brotliSize: true,
        }),
    ].filter(Boolean)
});
