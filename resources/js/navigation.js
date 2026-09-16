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

/** Regions swapped on navigation. Everything else stays exactly as it is. */
const REGIONS = ['main', '[data-nav-region]'];

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

/** Replace the changed regions of the page with the ones from the new document. */
const swapRegions = (incoming) => {
    let swapped = false;

    REGIONS.forEach((selector) => {
        const next = incoming.querySelector(selector);
        const current = document.querySelector(selector);

        if (next && current) {
            current.replaceWith(next);
            swapped = true;
        }
    });

    if (swapped) {
        document.title = incoming.title;
        mountIslands();
    }

    return swapped;
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
