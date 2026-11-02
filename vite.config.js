import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
                'resources/css/public.css',
                'resources/css/public-detail.css',
                'resources/css/public-checkout.css',
                'resources/css/public-payment.css',
                'resources/js/public.js',
            ],
            refresh: true,
        }),
    ],
});
