import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/auth.js',
                'resources/css/marketing.css',
                'resources/js/event-pack-print.js',
            ],
            refresh: true,
        }),
        // Bundle analyzer - only include in production builds with ANALYZE=true
        process.env.ANALYZE === 'true' &&
            visualizer({
                filename: 'public/build/bundle-analysis.html',
                open: false,
                gzipSize: true,
                brotliSize: true,
            }),
    ].filter(Boolean),
});
