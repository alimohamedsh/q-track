import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0', // يسمح بالاتصال من خارج الـ Container
        /**
         * مهم: لا تثبّت HMR host على دومين واحد (ngrok/lhr.life) لأن ده بيكسر
         * التحميل محلياً على 127.0.0.1 ويخلي الصفحات "من غير تنسيق".
         *
         * استخدم:
         * - محلياً: (لا تضبط VITE_HMR_HOST) → localhost
         * - مع ngrok: VITE_HMR_HOST=<ngrok-host> و VITE_HMR_PROTOCOL=wss
         */
        hmr: {
            host: process.env.VITE_HMR_HOST ?? 'localhost',
            protocol: process.env.VITE_HMR_PROTOCOL ?? 'ws',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});