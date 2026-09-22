import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Playfair Display', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Great Vibes', {
                    weights: [400],
                }),
                bunny('Cormorant Garamond', {
                    weights: [300, 400, 500, 600],
                }),
                bunny('Pinyon Script', {
                    weights: [400],
                }),
                bunny('Amiri', {
                    weights: [400],
                    subsets: ['arabic'],
                }),

                // The invitation designs. A card names its faces through
                // --f-d / --f-s / --f-r / --f-n, and the browser fetches only the
                // families the chosen design actually draws with. Self-hosted, like
                // the rest: the production CSP allows no font or style from
                // elsewhere (.ai/rules/general.md).
                bunny('Bodoni Moda', { weights: [400, 500, 600, 700] }),
                bunny('DM Serif Display', { weights: [400] }),
                bunny('Cinzel', { weights: [400, 500, 600, 700] }),
                bunny('Libre Baskerville', { weights: [400, 700] }),
                bunny('Lora', { weights: [400, 500, 600, 700] }),
                bunny('Abril Fatface', { weights: [400] }),
                bunny('Special Elite', { weights: [400] }),
                bunny('Montserrat', { weights: [300, 400, 500, 600, 700] }),
                bunny('Poppins', { weights: [300, 400, 500, 600] }),
                bunny('Lato', { weights: [300, 400, 700] }),
                bunny('Inter', { weights: [300, 400, 500, 600] }),
                bunny('Allura', { weights: [400] }),
                bunny('Parisienne', { weights: [400] }),
                bunny('Caveat', { weights: [400, 600] }),
            ],
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
