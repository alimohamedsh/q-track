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
        hmr: {
            host: '7078a6d47d686e.lhr.life', // الرابط بتاع النفق اللي شغال معاك دلوقتي
            protocol: 'wss', // استخدام Secure WebSocket للموبايل
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});