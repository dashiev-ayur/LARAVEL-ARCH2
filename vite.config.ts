import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const resourcesJs = path.resolve(path.dirname(fileURLToPath(import.meta.url)), 'resources/js');

export default defineConfig({
    resolve: {
        // Более специфичные алиасы FSD — до `@`, чтобы `import` резолвился в каталоги слоёв.
        alias: [
            { find: '@/shared', replacement: path.join(resourcesJs, 'shared') },
            { find: '@/entities', replacement: path.join(resourcesJs, 'entities') },
            { find: '@/features', replacement: path.join(resourcesJs, 'features') },
            { find: '@/widgets', replacement: path.join(resourcesJs, 'widgets') },
            { find: '@', replacement: resourcesJs },
        ],
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
});
