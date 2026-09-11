import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/admin/article-form.js',
                'resources/js/admin/pdf-thumbnail.js',
                'resources/js/admin/media-reorder.js',
                'resources/js/admin/file-dropzone.js',
                'resources/js/admin/page-blocks-reorder.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '127.0.0.1',
        hmr: {
            host: '127.0.0.1',
        },
    },
});