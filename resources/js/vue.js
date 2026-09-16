/**
 * Vue islands.
 *
 * Blade stays the page: it routes, authorises, renders the layout and writes
 * the meta tags search engines and WhatsApp read. Anything interactive is a Vue
 * component mounted into the element that declares it, so a public page ships
 * no JavaScript it does not use and nothing depends on a Node process.
 *
 * Usage in a view:
 *   <div data-vue="portfolio-gallery" data-props='@json($props)'></div>
 */
import { createApp } from 'vue';

const components = import.meta.glob('./components/**/*.vue', { eager: true });

/** "./components/vendor/PortfolioGallery.vue" becomes "portfolio-gallery". */
const nameOf = (path) =>
    path
        .split('/')
        .pop()
        .replace(/\.vue$/, '')
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .toLowerCase();

const registry = Object.fromEntries(
    Object.entries(components).map(([path, module]) => [nameOf(path), module.default]),
);

export const mountIslands = (root = document) => {
    root.querySelectorAll('[data-vue]:not([data-vue-mounted])').forEach((el) => {
        const component = registry[el.dataset.vue];

        if (!component) {
            console.error(`Unknown Vue component "${el.dataset.vue}".`, Object.keys(registry));
            return;
        }

        let props = {};
        try {
            props = el.dataset.props ? JSON.parse(el.dataset.props) : {};
        } catch (error) {
            console.error(`Bad props for "${el.dataset.vue}".`, error);
        }

        el.setAttribute('data-vue-mounted', '');
        createApp(component, props).mount(el);
    });
};
