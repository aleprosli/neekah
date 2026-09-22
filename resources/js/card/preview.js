/**
 * The editor's live preview.
 *
 * The server sends the chosen design (its canvases, palette and faces) and the
 * couple's saved card; this turns the form they are typing into, right now, into the
 * same props the public card is rendered from. That is why a name, a colour or a
 * section appears in the preview as it is typed rather than after a save.
 *
 * It mirrors App\Support\Card\CardWidgetData, which does the same job for the card a
 * guest opens. Keep the two in step: a section that exists in one and not the other
 * means the couple is previewing a card nobody will get.
 */
const PALETTE_ROLES = ['bg', 'bg2', 'onbg', 'head', 'acc', 'acc2', 'card', 'ink', 'pri', 'mut'];
const FONT_ROLES = ['d', 's', 'r', 'n'];

const FONT_KINDS = {
    serif: 'Georgia, serif',
    sans: 'system-ui, sans-serif',
    script: 'cursive',
    arabic: '"Traditional Arabic", serif',
};

/** Which fallback a family falls back to, from the picker's own grouping. */
const fontKinds = (fontOptions) => {
    const kinds = {};

    (fontOptions ?? []).forEach((group) => group.fonts.forEach((font) => (kinds[font.value] = group.kind)));

    return kinds;
};

const firstWord = (value) => (value || '').trim().split(/\s+/)[0] ?? '';

const shortName = (full, short) => (short || '').trim() || firstWord(full);

export const previewPalette = (design, overrides = {}) => {
    const palette = {};

    PALETTE_ROLES.forEach((role) => {
        palette[role] = overrides?.[role] || design.palette?.[role] || '#888888';
    });

    return palette;
};

export const previewFonts = (design, overrides = {}) => {
    const fonts = {};

    FONT_ROLES.forEach((role) => {
        fonts[role] = overrides?.[role] || design.fonts?.[role];
    });

    return fonts;
};

export const previewVariables = (palette, fonts, kinds) => {
    const vars = {};

    PALETTE_ROLES.forEach((role) => (vars[`--c-${role}`] = palette[role]));
    FONT_ROLES.forEach((role) => {
        if (fonts[role]) vars[`--f-${role}`] = `"${fonts[role]}", ${FONT_KINDS[kinds[fonts[role]]] ?? FONT_KINDS.sans}`;
    });

    return vars;
};

const filled = (value) => typeof value === 'string' && value.trim() !== '';

const rows = (list, ...required) =>
    (list ?? []).filter((row) => required.every((field) => filled(row?.[field])));

/** The moment the majlis begins, in the browser's own zone. */
const eventStart = (form) => {
    if (!filled(form.event_date)) return null;

    return `${form.event_date}T${filled(form.starts_at) ? form.starts_at : '00:00'}:00`;
};

const mapUrl = (form) => {
    if (filled(form.map_url)) return form.map_url;

    const query = `${form.venue_name ?? ''} ${form.venue_address ?? ''}`.trim();

    return query === '' ? null : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
};

/**
 * One widget's data, or null when the couple has given it nothing to show — in the
 * editor an empty section is still drawn, so they can see what they just turned on.
 */
const widgetData = (key, form, extras, labels) => {
    switch (key) {
        case 'countdown':
            return { target: eventStart(form) };
        case 'itinerary':
            return { rows: rows(form.itinerary, 'time', 'label') };
        case 'location':
            return {
                venue: form.venue_name ?? '',
                address: form.venue_address ?? '',
                mapUrl: mapUrl(form),
                calendarUrl: null,
            };
        case 'gallery':
            return { photos: extras.photos ?? [] };
        case 'rsvp':
            return { open: Boolean(form.rsvp_enabled), deadline: form.rsvp_deadline ?? null, action: null, guest: null };
        case 'wishes':
            return { items: extras.wishes ?? [], note: labels.wishes_note ?? '' };
        case 'gift':
            return {
                note: form.gift_note ?? '',
                accounts: rows(form.gift_accounts, 'bank', 'number'),
                qrUrl: extras.giftQrUrl ?? null,
            };
        case 'contacts':
            return {
                items: rows(form.contacts, 'name', 'phone').map((contact) => ({ ...contact, whatsapp: null })),
            };
        case 'closing':
            return {
                note: form.closing_note ?? '',
                signature: `${shortName(form.groom_name, form.groom_short)} & ${shortName(form.bride_name, form.bride_short)}`,
            };
        default:
            return null;
    }
};

/**
 * The {{token}} values the artwork prints, taken from the form rather than the
 * saved card.
 */
export const previewContent = (form) => ({
    groom: form.groom_name ?? '',
    bride: form.bride_name ?? '',
    groom_short: shortName(form.groom_name, form.groom_short),
    bride_short: shortName(form.bride_name, form.bride_short),
    date: form.event_date ?? '',
    time: form.starts_at ?? '',
    end_time: form.ends_at ?? '',
    venue: form.venue_name ?? '',
    address: form.venue_address ?? '',
    invite_message: form.invitation_note || form.salutation || '',
    salutation: form.salutation ?? '',
    groom_father: form.groom_father ?? '',
    groom_mother: form.groom_mother ?? '',
    bride_father: form.bride_father ?? '',
    bride_mother: form.bride_mother ?? '',
    groom_bio: form.groom_bio ?? '',
    bride_bio: form.bride_bio ?? '',
    closing_note: form.closing_note ?? '',
    guest: '',
});

/**
 * Everything CardView.vue needs, from the design and the unsaved form.
 *
 * @param {object} design    the chosen design as the server described it
 * @param {object} form      the editor's live form values
 * @param {object} extras    {photos, wishes, giftQrUrl, slotPhotos, fontOptions, labels}
 */
export const buildPreviewProps = (design, form, extras = {}) => {
    const labels = extras.labels ?? {};
    const palette = previewPalette(design, form.palette);
    const fonts = previewFonts(design, form.fonts);

    return {
        preview: true,
        canvases: design.canvases ?? [],
        content: previewContent(form),
        photos: extras.slotPhotos ?? {},
        palette,
        fonts,
        vars: previewVariables(palette, fonts, fontKinds(extras.fontOptions)),
        labels,
        gate: { enabled: false },
        music: null,
        // The headings are the card's own words, the same ones the public card
        // prints — the editor's section list uses longer labels of its own.
        widgets: (form.widgets ?? []).map((key) => ({
            key,
            heading: labels[key] ?? '',
            ...widgetData(key, form, extras, labels),
        })),
    };
};
