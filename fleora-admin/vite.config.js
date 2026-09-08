import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Polices servies depuis notre propre domaine via Bunny, et non
            // par Google Fonts : aucune requête vers un tiers, donc aucune
            // adresse IP de visiteuse transmise — un point de moins à déclarer
            // dans la politique de confidentialité (Loi 25).
            fonts: [
                bunny('Cormorant Garamond', {
                    weights: [300, 400, 500, 600],
                }),
                bunny('Inter', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
