import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/remixicon.css',
                'resources/css/remixicon-v3.css',
                'resources/css/lucide.css',
                'resources/css/manrope.css',
                'resources/css/instrument-sans.css',
                'resources/js/app.js',
                'resources/js/seller-product-form.js'
            ],
            refresh: true,
        }),
    ],
});
