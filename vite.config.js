import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import {
    nativephpMobile,
    nativephpHotFile,
} from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

export default defineConfig({
    resolve: {
        alias: {
            '#nativephp': new URL(
                './vendor/nativephp/mobile/resources/dist/native.js',
                import.meta.url
            ).pathname,
        },
    },
    server: {
        host: true,
        strictPort: true,
        port: 5173,
        hmr: {
            host: 'vite-muttasiq.dev.localhost',
            protocol: 'wss',
            clientPort: 443,
        },
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/app-lazy.css',
                'resources/js/app.js',
                'resources/css/core/filament/panels.css',
                'resources/css/core/filament/components.css',
            ],
            refresh: true,
            hotFile: nativephpHotFile(),
        }),
        nativephpMobile(),
    ],
});
