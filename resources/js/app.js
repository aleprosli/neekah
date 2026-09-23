/**
 * Vue components declared with data-vue are mounted first, so an island is
 * interactive as early as possible. Everything below is the plain-JavaScript
 * behaviour that does not need a component.
 */
import './contact-beacon.js';
import './form-guard.js';
import './navigation.js';
import { mountIslands } from './vue.js';

mountIslands();

/**
 * Take the preloader down at whichever comes later, the minimum hold set in
 * config('neekah.preloader.seconds') or the page finishing loading. Also take
 * it down when the browser restores the page from its back/forward cache,
 * where `load` never fires a second time.
 */
(() => {
    // Remembered for the inline script in the layout head, which hides the
    // preloader before the first paint on every later arrival in this tab.
    try {
        sessionStorage.setItem('neekah:arrived', '1');
    } catch {
        // A private window without storage simply keeps seeing the preloader.
    }

    const overlay = document.getElementById('nk-preloader');
    if (!overlay) {
        return;
    }

    const startedAt = performance.now();
    const holdMs = Math.max(0, parseFloat(overlay.dataset.minSeconds || '0') * 1000);

    const remove = () => {
        overlay.classList.add('is-done');
        overlay.addEventListener('transitionend', () => overlay.remove(), { once: true });
        // A removed element fires no transitionend, so do not rely on it alone.
        setTimeout(() => overlay.remove(), 600);
    };

    const dismiss = () => setTimeout(remove, Math.max(0, holdMs - (performance.now() - startedAt)));

    if (document.readyState === 'complete') {
        dismiss();
    } else {
        window.addEventListener('load', dismiss, { once: true });
    }

    window.addEventListener('pageshow', (event) => event.persisted && remove());
})();

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) {
        document.getElementById(opener.dataset.dialogOpen)?.showModal();
        return;
    }

    const closer = event.target.closest('[data-dialog-close]');
    if (closer) {
        closer.closest('dialog')?.close();
        return;
    }

    if (event.target instanceof HTMLDialogElement) {
        event.target.close();
    }
});

document.addEventListener(
    'toggle',
    (event) => {
        const details = event.target;
        if (!(details instanceof HTMLDetailsElement) || !details.open || details.dataset.popover === undefined) {
            return;
        }

        document.querySelectorAll('details[data-popover][open]').forEach((other) => {
            if (other !== details) {
                other.open = false;
            }
        });
    },
    true,
);

document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-popover][open]').forEach((details) => {
        if (!details.contains(event.target)) {
            details.open = false;
        }
    });
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) {
        return;
    }

    try {
        await navigator.clipboard.writeText(button.dataset.copy);
    } catch {
        return;
    }

    const original = button.textContent;
    button.textContent = 'Disalin!';
    setTimeout(() => {
        button.textContent = original;
    }, 2000);
});

/**
 * The phone's own share sheet, which is the only way into Instagram, TikTok
 * and the rest. The button stays hidden where the browser has none.
 */
const revealShareButtons = () => {
    if (navigator.share) {
        document.querySelectorAll('[data-share-item]').forEach((item) => (item.hidden = false));
    }
};

revealShareButtons();
window.addEventListener('neekah:navigated', revealShareButtons);

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-share-url]');
    if (button) {
        // Rejects when the visitor closes the sheet, which is not an error.
        navigator.share({ title: button.dataset.shareTitle, url: button.dataset.shareUrl }).catch(() => {});
    }
});

/**
 * Horizontal scrollers ([data-scroller]): arrows for a desktop, which has no
 * swipe, shown only when there is more to see that way, and an edge fade.
 */
const updateScroller = (scroller) => {
    const track = scroller.querySelector('[data-scroll-track]');
    if (!track) {
        return;
    }

    const before = track.scrollLeft > 4;
    const after = track.scrollLeft + track.clientWidth < track.scrollWidth - 4;

    scroller.querySelector('[data-scroll-prev]')?.toggleAttribute('hidden', !before);
    scroller.querySelector('[data-scroll-next]')?.toggleAttribute('hidden', !after);

    if (before && after) {
        track.dataset.fade = 'both';
    } else if (after) {
        track.dataset.fade = 'end';
    } else if (before) {
        track.dataset.fade = 'start';
    } else {
        delete track.dataset.fade;
    }
};

const updateScrollers = () => document.querySelectorAll('[data-scroller]').forEach(updateScroller);

updateScrollers();
window.addEventListener('resize', updateScrollers);
window.addEventListener('load', updateScrollers);
window.addEventListener('neekah:navigated', updateScrollers);

document.addEventListener(
    'scroll',
    (event) => {
        const scroller = event.target instanceof Element ? event.target.closest('[data-scroller]') : null;
        if (scroller) {
            updateScroller(scroller);
        }
    },
    true,
);

document.addEventListener('click', (event) => {
    const arrow = event.target.closest('[data-scroll-prev], [data-scroll-next]');
    if (!arrow) {
        return;
    }

    const track = arrow.closest('[data-scroller]')?.querySelector('[data-scroll-track]');
    const direction = arrow.matches('[data-scroll-next]') ? 1 : -1;
    track?.scrollBy({ left: direction * track.clientWidth * 0.8, behavior: 'smooth' });
});

/**
 * Vendor comparison tray. Selections live in sessionStorage so they survive
 * paging and filtering, and travel to /compare as a query string.
 */
(() => {
    const KEY = 'neekah:compare';
    const MAX = 4;
    const tray = document.querySelector('[data-compare-tray]');
    if (!tray) {
        return;
    }

    const read = () => {
        try {
            return JSON.parse(sessionStorage.getItem(KEY)) ?? [];
        } catch {
            return [];
        }
    };

    const write = (list) => {
        try {
            sessionStorage.setItem(KEY, JSON.stringify(list));
        } catch {
            // A private window without storage still gets a working page.
        }
    };

    const render = () => {
        const list = read();
        tray.hidden = list.length === 0;
        tray.querySelector('[data-compare-count]').textContent = list.length;
        tray.querySelector('[data-compare-names]').textContent = list.map((item) => item.name).join(', ');
        tray.querySelector('[data-compare-link]').href =
            '/compare?' + list.map((item) => 'vendors[]=' + encodeURIComponent(item.slug)).join('&');

        document.querySelectorAll('[data-compare]').forEach((box) => {
            box.checked = list.some((item) => item.slug === box.dataset.compare);
        });
    };

    document.addEventListener('change', (event) => {
        const box = event.target.closest('[data-compare]');
        if (!box) {
            return;
        }

        const list = read().filter((item) => item.slug !== box.dataset.compare);

        if (box.checked) {
            if (list.length >= MAX) {
                box.checked = false;
                tray.querySelector('[data-compare-limit]').hidden = false;
                setTimeout(() => (tray.querySelector('[data-compare-limit]').hidden = true), 2500);
                return;
            }
            list.push({ slug: box.dataset.compare, name: box.dataset.compareName });
        }

        write(list);
        render();
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-compare-clear]')) {
            write([]);
            render();
        }
    });

    render();
})();
