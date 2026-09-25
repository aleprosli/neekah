/**
 * Collapsible groups in the dashboard sidebar (<details data-nav-group>).
 *
 * The sidebar is swapped on every navigation (navigation.js), so the server
 * markup decides the default and this puts back what the person chose last:
 * a group they closed stays closed, unless it holds the page they are on.
 * Stored per browser; a private window simply gets the defaults.
 */
const KEY = 'neekah:nav-closed';

const read = () => {
    try {
        return new Set(JSON.parse(window.localStorage.getItem(KEY) || '[]'));
    } catch {
        return new Set();
    }
};

const write = (closed) => {
    try {
        window.localStorage.setItem(KEY, JSON.stringify([...closed]));
    } catch {
        // Storage refused: the choice lasts until the next page, which is fine.
    }
};

const restore = () => {
    const closed = read();

    document.querySelectorAll('details[data-nav-group]').forEach((group) => {
        const holdsCurrent = group.querySelector('[aria-current="page"]') !== null;
        group.open = holdsCurrent || !closed.has(group.dataset.navGroup);
    });
};

// "toggle" does not bubble, so it is caught on the way down.
document.addEventListener(
    'toggle',
    (event) => {
        const group = event.target;

        if (!(group instanceof HTMLDetailsElement) || !group.dataset.navGroup) {
            return;
        }

        const closed = read();
        group.open ? closed.delete(group.dataset.navGroup) : closed.add(group.dataset.navGroup);
        write(closed);
    },
    true,
);

restore();
window.addEventListener('neekah:navigated', restore);
