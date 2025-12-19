import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Determine host based on environment
const getHmrHost = () => {
    // Check if we're in production/staging
    if (process.env.APP_ENV === 'production' || process.env.VITE_HMR_HOST) {
        return process.env.VITE_HMR_HOST;
    }
    // Default to local development host
    return 'laravel-ecosurvey.ddev.site';
};

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    optimizeDeps: {
        include: ['ckeditor5'],
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: getHmrHost(),
            protocol: 'wss',
            port: 5173,
        },
    },
});
