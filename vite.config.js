import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',
        manifest: 'manifest.json',
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(-1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|webp/i.test(extType)) {
                        extType = 'images';
                    }
                    return `assets/${extType}-[name]-[hash][extname]`;
                },
                chunkFileNames: 'assets/js-[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
            },
        },
    },
});
