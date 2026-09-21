/**
 * The strings a Vue island prints.
 *
 * Islands are mounted from Blade, which cannot hand __() down into them, so
 * the page carries a dictionary and this reads it once. A key with no
 * translation returns the key itself: a page reading "auth.forgot_password"
 * is obviously wrong, where an empty string just looks like a broken layout.
 */
const read = () => {
    const tag = document.getElementById('translations');

    if (!tag) return {};

    try {
        return JSON.parse(tag.textContent) ?? {};
    } catch {
        return {};
    }
};

let dictionary = read();

/** Re-read after navigation.js swaps in a page rendered in another language. */
export const refreshTranslations = () => {
    dictionary = read();
};

export const t = (key, replace = {}) => {
    const value = key.split('.').reduce((carry, part) => carry?.[part], dictionary);

    if (typeof value !== 'string') return key;

    return Object.entries(replace).reduce(
        (carry, [name, value_]) => carry.replaceAll(`:${name}`, value_),
        value,
    );
};
