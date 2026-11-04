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
                'resources/css/public-responsive.css',
                'resources/css/public-home.css',
                'resources/css/public-partner.css',
                'resources/css/public-partner-form.css',
                'resources/css/public-program-overrides.css',
                'resources/css/public-campaign-media.css',
                'resources/css/public-organization-profile.css',
                'resources/css/public-detail.css',
                'resources/css/public-checkout.css',
                'resources/css/public-payment.css',
                'resources/css/public-account.css',
                'resources/js/public.js',
            ],
            refresh: true,
        }),
    ],
});
