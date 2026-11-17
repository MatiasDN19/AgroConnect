import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    // loadEnv lee el .env (process.env no lo trae solo en este archivo).
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.tsx',
                refresh: true,
            }),
            react(),
        ],
        server: {
            watch: {
                // Con Docker sobre archivos de Windows (sin WSL), Vite no se entera
                // de los cambios. El polling revisa los archivos cada tanto.
                // Solo se activa si VITE_USE_POLLING=true en el .env de cada uno.
                usePolling: env.VITE_USE_POLLING === 'true',
                interval: 300,
                // Carpetas sin código del frontend: no vale la pena vigilarlas.
                // Sobre todo vendor/ (miles de archivos PHP): con polling,
                // revisarlo todo el tiempo satura el disco y todo carga lento.
                ignored: [
                    '**/vendor/**',
                    '**/storage/**',
                    '**/bootstrap/cache/**',
                    '**/public/build/**',
                    '**/tests/**',
                    '**/database/**',
                ],
            },
        },
    };
});
