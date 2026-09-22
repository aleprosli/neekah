/**
 * The {{tokens}} printed inside a design's text layers.
 *
 * The artwork ships with tokens rather than the couple's words, so the editor can
 * redraw the card as they type without asking the server to recompose anything.
 * Dates are formatted here, in Malay, because a card is Malay by decision
 * (.ai/rules/lang.md) even when the dashboard around it is in English.
 */

const LOCALE = 'ms-MY';

/** Friendlier names the designs are written with. */
const ALIASES = {
    groom_name: 'groom',
    bride_name: 'bride',
    venue_name: 'venue',
    venue_address: 'address',
    wedding_message: 'invite_message',
    wedding_date: 'date_long',
    wedding_time: 'time_12',
};

const parseDate = (value) => {
    if (!value) return null;

    const [year, month, day] = String(value).split('-').map(Number);

    if (!year || !month || !day) return null;

    const date = new Date(year, month - 1, day);

    return Number.isNaN(date.getTime()) ? null : date;
};

const formatDate = (value, options) => {
    const date = parseDate(value);

    return date ? new Intl.DateTimeFormat(LOCALE, options).format(date) : '';
};

/** "11:00" as "11:00 AM"; a card prints the clock the way a guest reads it. */
const format12Hour = (value) => {
    if (!value) return '';

    const [hours, minutes] = String(value).split(':').map(Number);

    if (Number.isNaN(hours)) return '';

    const suffix = hours < 12 ? 'AM' : 'PM';
    const hour = hours % 12 === 0 ? 12 : hours % 12;

    return `${hour}:${String(minutes || 0).padStart(2, '0')} ${suffix}`;
};

const initial = (value) => (value ? value.trim().charAt(0).toUpperCase() : '');

const derive = (token, content) => {
    const value = (key) => content[key] ?? '';

    switch (token) {
        case 'date_long':
            return formatDate(value('date'), { day: 'numeric', month: 'long', year: 'numeric' });
        case 'date_full':
            return formatDate(value('date'), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        case 'day':
            return formatDate(value('date'), { day: 'numeric' });
        case 'month':
            return formatDate(value('date'), { month: 'long' });
        case 'year':
            return formatDate(value('date'), { year: 'numeric' });
        case 'weekday':
            return formatDate(value('date'), { weekday: 'long' });
        case 'time_12':
            return format12Hour(value('time'));
        case 'end_time_12':
            return format12Hour(value('end_time'));
        case 'names':
            return `${value('groom_short') || value('groom')} & ${value('bride_short') || value('bride')}`;
        case 'groom_initial':
            return initial(value('groom_short') || value('groom'));
        case 'bride_initial':
            return initial(value('bride_short') || value('bride'));
        case 'initials':
            return `${initial(value('groom_short') || value('groom'))} & ${initial(value('bride_short') || value('bride'))}`;
        default:
            return null;
    }
};

export const resolveTokens = (text, content = {}) => {
    if (typeof text !== 'string' || !text.includes('{{')) return text ?? '';

    return text.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/g, (match, raw) => {
        const token = ALIASES[raw] ?? raw;
        const derived = derive(token, content);

        return derived ?? content[token] ?? '';
    });
};

export { format12Hour, formatDate };
