import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/adminlte.css',
                'resources/css/enfas-agenda.css',
                'resources/js/adminlte.js',
                'resources/js/enfas-scan.js',
            ],
            refresh: true,
        }),
    ],
});
