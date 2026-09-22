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
import { refreshTranslations, t } from './i18n.js';

/**
 * Lazy on purpose. Each component becomes its own chunk, so a public page
 * downloads the one or two islands it actually declares instead of every
 * admin table, chart and card section in the application.
 */
const components = import.meta.glob('./components/**/*.vue');

/** "./components/vendor/PortfolioGallery.vue" becomes "portfolio-gallery". */
const nameOf = (path) =>
    path
        .split('/')
        .pop()
        .replace(/\.vue$/, '')
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .toLowerCase();

const registry = Object.fromEntries(
    Object.entries(components).map(([path, load]) => [nameOf(path), load]),
);

/**
 * Mount one element. Its chunk is fetched on demand; until it arrives the
 * server-rendered fallback inside the element is what the visitor sees, which
 * is the same thing a visitor without JavaScript gets.
 */
const mountIsland = async (el) => {
    const load = registry[el.dataset.vue];

    if (!load) {
        console.error(`Unknown Vue component "${el.dataset.vue}".`, Object.keys(registry));
        return;
    }

    let props = {};
    try {
        props = el.dataset.props ? JSON.parse(el.dataset.props) : {};
    } catch (error) {
        console.error(`Bad props for "${el.dataset.vue}".`, error);
    }

    // Claimed before the await, so a navigation landing mid-fetch cannot
    // start a second mount on the same element.
    el.setAttribute('data-vue-mounted', '');

    let module;
    try {
        module = await load();
    } catch (error) {
        console.error(`Could not load Vue component "${el.dataset.vue}".`, error);
        el.removeAttribute('data-vue-mounted');
        return;
    }

    // The page was swapped out while its chunk was in flight.
    if (!el.isConnected) {
        return;
    }

    const app = createApp(module.default, props);
    app.config.globalProperties.$t = t;
    app.mount(el);
};

/**
 * Mount when the element is near the viewport, for an island the visitor may
 * never scroll to. Opt-in with data-vue-lazy, because an island inside a
 * closed dialog never intersects anything and must still mount straight away.
 *
 * Falls back to mounting immediately where the browser has no observer.
 */
const observer =
    typeof IntersectionObserver === 'undefined'
        ? null
        : new IntersectionObserver(
              (entries, self) =>
                  entries.forEach((entry) => {
                      if (entry.isIntersecting) {
                          self.unobserve(entry.target);
                          mountIsland(entry.target);
                      }
                  }),
              { rootMargin: '300px' },
          );

export const mountIslands = (root = document) => {
    // A swapped-in page may be in the other language.
    refreshTranslations();

    const islands = [...root.querySelectorAll('[data-vue]:not([data-vue-mounted])')];

    if (observer) {
        islands.filter((el) => el.dataset.vueLazy !== undefined).forEach((el) => observer.observe(el));
    }

    return Promise.all(
        islands
            .filter((el) => !observer || el.dataset.vueLazy === undefined)
            .map(mountIsland),
    );
};
