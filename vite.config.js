import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import laravelHotRefresh from 'laravel-hot-refresh';
import {
    nativephpMobile,
    nativephpHotFile,
} from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

export default defineConfig(({ mode }) => {
    const environment = loadEnv(mode, process.cwd(), '');
    const reverbAppKey = process.env.REVERB_APP_KEY || environment.REVERB_APP_KEY || '';

    return {
        define: {
            'import.meta.env.VITE_REVERB_APP_KEY': JSON.stringify(reverbAppKey),
        },
        server: {
            host: true,
            strictPort: true,
            port: 5173,
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
            hmr: {
                host: 'vite-muttasiq.dev.localhost',
                protocol: 'wss',
                clientPort: 443,
            },
        },
        build: {
            emptyOutDir: false,
        },
        plugins: [
            laravelHotRefresh({
                refresh: [
                    'resources/css/app.css',
                    'resources/css/app-lazy.css',
                    'resources/css/core/filament/panels.css',
                    'resources/css/core/filament/components.css',
                ],
                defaultWatches: [
                    'app/Filament/**/*.php',
                    'app/Livewire/**/*.php',
                    'app/View/Components/**/*.php',
                    'resources/views/**/*.blade.php',
                ],
            }),
            tailwindcss(),
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/css/app-lazy.css',
                    'resources/js/app.js',
                    'resources/css/core/filament/panels.css',
                    'resources/css/core/filament/components.css',
                ],
                refresh: false,
                hotFile: nativephpHotFile(),
            }),
            nativephpMobile(),
        ],
        resolve: {
            alias: {
                '#nativephp': new URL(
                    './vendor/nativephp/mobile/resources/dist/native.js',
                    import.meta.url,
                ).pathname,
            },
        },
    };
});
