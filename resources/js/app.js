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
