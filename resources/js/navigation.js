/**
 * Page-swap navigation.
 *
 * Every page is still rendered by the server, which is what keeps meta tags,
 * SEO and link previews working. This only saves the browser from throwing the
 * whole document away on every click: a link is fetched, the parts that
 * actually changed are swapped in, and the Vue components on the new markup are
 * mounted. Anything it cannot handle falls through to an ordinary page load, so
 * a failure here is never worse than what the browser would have done.
 */
import { mountIslands } from './vue.js';

/**
 * Regions swapped on navigation. Everything else stays exactly as it is.
 *
 * A dashboard page holds its <main> inside a grid beside a sidebar, and a
 * public page does not, so swapping <main> alone between the two would leave
 * the dashboard furniture standing around a public page. The shell meta tag
 * below is what stops that: a different shell means an ordinary page load.
 */
const REGIONS = ['main', '[data-nav-region]'];

const shellOf = (doc) => doc.querySelector('meta[name="page-shell"]')?.content ?? null;

/**
 * What sits around the swapped regions: the top-level elements of <body>, by
 * tag, id and data attributes, but not their content.
 *
 * Only <main> and the navigations are swapped, so a page whose furniture
 * differs cannot be swapped into — the vendor list's search bar, compare tray
 * and filter dialog would stay standing on a blog post. Read from the server's
 * markup, never the live DOM, because Vue teleports its dialogs into <body>.
 */
const furnitureOf = (doc) =>
    [...doc.body.children]
        .filter((element) => !['SCRIPT', 'TEMPLATE'].includes(element.tagName))
        .map((element) =>
            [
                element.tagName,
                element.id,
                ...element.getAttributeNames().filter((name) => name.startsWith('data-')),
            ].join(' '),
        )
        .join('|');

/** Evaluated before any island mounts, so this is still the server's markup. */
let currentFurniture = furnitureOf(document);

const currentUrl = () => window.location.href;

const isSwappable = (link, event) => {
    if (event.defaultPrevented || event.button !== 0) return false;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download') || link.dataset.noSwap !== undefined) return false;

    const url = new URL(link.href, currentUrl());

    // Another site, a download, or a jump within this same page: let the
    // browser do what it always does.
    return (
        url.origin === window.location.origin &&
        !url.pathname.match(/\.(pdf|zip|csv|png|jpe?g|webp|ics)$/i) &&
        !(url.pathname === window.location.pathname && url.hash)
    );
};

const showProgress = () => {
    document.documentElement.classList.add('nk-navigating');

    return () => document.documentElement.classList.remove('nk-navigating');
};

/**
 * Replace the changed regions of the page with the ones from the new document.
 *
 * Regions are matched pairwise: a dashboard page has two navigations that both
 * show an active item, and taking only the first would leave the sidebar
 * pointing at the page the visitor just left.
 */
const swapRegions = (incoming) => {
    const incomingFurniture = furnitureOf(incoming);

    if (shellOf(incoming) !== shellOf(document) || incomingFurniture !== currentFurniture) {
        return false;
    }

    const pairs = REGIONS.map((selector) => [document.querySelectorAll(selector), incoming.querySelectorAll(selector)]);

    // A region on one page and not the other would be left behind, or never
    // arrive, so a mismatch anywhere means an ordinary page load.
    if (pairs.some(([current, next]) => current.length !== next.length) || !pairs[0][1].length) {
        return false;
    }

    pairs.forEach(([current, next]) => current.forEach((element, at) => element.replaceWith(next[at])));

    currentFurniture = incomingFurniture;
    document.title = incoming.title;
    mountIslands();

    return true;
};

const visit = async (url, { push = true } = {}) => {
    const done = showProgress();

    try {
        const response = await fetch(url, {
            headers: { 'X-Page-Swap': '1', Accept: 'text/html' },
            credentials: 'same-origin',
        });

        // A redirect to a login page, an error page, anything unexpected: hand
        // it back to the browser rather than guessing.
        if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) {
            window.location.href = url;
            return;
        }

        const incoming = new DOMParser().parseFromString(await response.text(), 'text/html');

        if (!swapRegions(incoming)) {
            window.location.href = response.url || url;
            return;
        }

        if (push) {
            window.history.pushState({ swapped: true }, '', response.url || url);
        }

        window.scrollTo({ top: 0 });
    } catch {
        window.location.href = url;
    } finally {
        done();
    }
};

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');

    if (!link || !isSwappable(link, event)) {
        return;
    }

    event.preventDefault();
    visit(link.href);
});

// Back and forward have to re-fetch, because the swapped-out markup is gone.
window.addEventListener('popstate', (event) => {
    if (event.state?.swapped) {
        visit(currentUrl(), { push: false });
    }
});

// So the first Back press from a swapped page has somewhere to return to.
window.history.replaceState({ swapped: true }, '');
