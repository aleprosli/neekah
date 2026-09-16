/**
 * Vue components declared with data-vue are mounted first, so an island is
 * interactive as early as possible. Everything below is the plain-JavaScript
 * behaviour that does not need a component.
 */
import { mountIslands } from './vue.js';

mountIslands();

/**
 * The article editor only exists on the admin blog form, so TipTap and
 * ProseMirror load on demand instead of weighing down every public page.
 */
if (document.querySelector('[data-rich-editor]')) {
    import('./editor.js').then(({ mountEditors }) => mountEditors());
}

/**
 * Take the preloader down at whichever comes later, the minimum hold set in
 * config('neekah.preloader.seconds') or the page finishing loading. Also take
 * it down when the browser restores the page from its back/forward cache,
 * where `load` never fires a second time.
 */
(() => {
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

/**
 * Wedding invitation cards: the opening gate, the live countdown and
 * scroll reveals. All of it no-ops on pages without a card.
 */
(() => {
    const card = document.querySelector('[data-card]');
    if (!card) {
        return;
    }

    // Opening gate: the card stays sealed until the guest opens it.
    const gate = document.querySelector('[data-gate]');
    const open = () => {
        document.documentElement.classList.add('nk-open');
        document.body.style.overflow = '';
        setTimeout(() => gate?.remove(), 1000);
    };

    if (gate) {
        document.body.style.overflow = 'hidden';
        gate.querySelectorAll('[data-gate-open]').forEach((button) => button.addEventListener('click', open));
    }

    // Countdown to the moment the ceremony begins.
    const clock = document.querySelector('[data-countdown]');
    if (clock) {
        const target = new Date(clock.dataset.countdown).getTime();
        const units = {
            days: clock.querySelector('[data-unit="days"]'),
            hours: clock.querySelector('[data-unit="hours"]'),
            minutes: clock.querySelector('[data-unit="minutes"]'),
            seconds: clock.querySelector('[data-unit="seconds"]'),
        };

        const tick = () => {
            const left = Math.max(0, target - Date.now());
            const second = 1000;
            const values = {
                days: Math.floor(left / (second * 60 * 60 * 24)),
                hours: Math.floor((left / (second * 60 * 60)) % 24),
                minutes: Math.floor((left / (second * 60)) % 60),
                seconds: Math.floor((left / second) % 60),
            };

            Object.entries(units).forEach(([unit, node]) => {
                if (node) {
                    node.textContent = String(values[unit]).padStart(2, '0');
                }
            });
        };

        tick();
        setInterval(tick, 1000);
    }

    // Sections rise into view as the guest scrolls.
    const reveals = document.querySelectorAll('[data-reveal]');
    if (reveals.length && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { rootMargin: '0px 0px -12% 0px' },
        );

        reveals.forEach((node) => observer.observe(node));
    } else {
        reveals.forEach((node) => node.classList.add('is-visible'));
    }
})();

/**
 * Package contents repeater on the vendor package form: one input per item,
 * with add, remove, Enter to continue and drag to reorder. Rows are cloned
 * from the first one, so the markup lives in the Blade view only.
 */
(() => {
    const list = document.querySelector('[data-feature-list]');
    if (!list) {
        return;
    }

    const items = list.querySelector('[data-feature-items]');
    const blank = items.querySelector('[data-feature-item]').cloneNode(true);
    blank.querySelector('input').value = '';

    const refresh = () => {
        const rows = items.querySelectorAll('[data-feature-item]');
        list.querySelector('[data-feature-count]').textContent = rows.length;
        // The last row cannot go: an empty list would leave nothing to submit.
        rows.forEach((row) => (row.querySelector('[data-feature-remove]').disabled = rows.length === 1));
    };

    const addRow = (after = null) => {
        const row = blank.cloneNode(true);
        after ? after.after(row) : items.append(row);
        row.querySelector('input').focus();
        refresh();
    };

    list.querySelector('[data-feature-add]').addEventListener('click', () => addRow());

    items.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-feature-remove]');
        if (!remove || items.querySelectorAll('[data-feature-item]').length === 1) {
            return;
        }

        remove.closest('[data-feature-item]').remove();
        refresh();
    });

    items.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || !event.target.matches('input')) {
            return;
        }

        event.preventDefault();
        addRow(event.target.closest('[data-feature-item]'));
    });

    // Drag to reorder. The handle turns its row draggable only while held, so
    // text selection inside the inputs keeps working.
    let dragging = null;

    items.addEventListener('pointerdown', (event) => {
        const handle = event.target.closest('[data-feature-handle]');
        if (handle) {
            handle.closest('[data-feature-item]').draggable = true;
        }
    });

    items.addEventListener('dragstart', (event) => {
        dragging = event.target.closest('[data-feature-item]');
        dragging.classList.add('opacity-50');
    });

    items.addEventListener('dragover', (event) => {
        if (!dragging) {
            return;
        }

        event.preventDefault();
        const over = event.target.closest('[data-feature-item]');
        if (!over || over === dragging) {
            return;
        }

        const after = over.getBoundingClientRect().top + over.offsetHeight / 2 < event.clientY;
        after ? over.after(dragging) : over.before(dragging);
    });

    items.addEventListener('dragend', () => {
        dragging.classList.remove('opacity-50');
        dragging.draggable = false;
        dragging = null;
    });

    refresh();
})();
