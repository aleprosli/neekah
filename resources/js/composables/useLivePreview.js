import { onBeforeUnmount, ref } from 'vue';

/**
 * The card, drawn live beside the form that is editing it.
 *
 * Two speeds, because the card is already built on CSS custom properties.
 * Colours, faces and paper are written straight into the frame's own card
 * element, which costs nothing and lands as fast as the colour picker moves.
 * Everything else — layout, artwork, the order of the sections, the words —
 * is redrawn by the server, so the preview is the same Blade the guests get
 * rather than a second copy of the card written in JavaScript.
 */
export const useLivePreview = ({ url, csrf, afterDraw = () => {} }) => {
    const frame = ref(null);
    const drawing = ref(false);
    const failed = ref(false);

    let pending = null;
    let inFlight = null;

    const cardRoot = () => {
        try {
            return frame.value?.contentDocument?.querySelector('[data-card-root]') ?? null;
        } catch {
            return null; // A frame mid-navigation has no document yet.
        }
    };

    /** Colours, faces and paper, applied without redrawing anything. */
    const paint = (variables) => {
        const root = cardRoot();
        if (!root) return false;

        Object.entries(variables).forEach(([name, value]) => {
            if (value) root.style.setProperty(name, value);
        });

        return true;
    };

    const redraw = (payload) => {
        pending = payload;

        if (inFlight) return;

        inFlight = (async () => {
            while (pending) {
                const body = pending;
                pending = null;
                drawing.value = true;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'text/html' },
                        body,
                    });

                    if (!response.ok) throw new Error(String(response.status));

                    const html = await response.text();
                    const doc = frame.value?.contentDocument;

                    if (doc) {
                        // Replacing the document keeps the frame's own scroll
                        // position out of it; open() wipes and rewrites.
                        doc.open();
                        doc.write(html);
                        doc.close();

                        // Everything the frame cannot know on its own — the
                        // colours just picked, a picture still on this machine.
                        afterDraw(doc);
                    }

                    failed.value = false;
                } catch {
                    failed.value = true;
                } finally {
                    drawing.value = false;
                }
            }

            inFlight = null;
        })();
    };

    let timer = null;

    const redrawSoon = (build, wait = 400) => {
        clearTimeout(timer);
        timer = setTimeout(() => redraw(build()), wait);
    };

    onBeforeUnmount(() => clearTimeout(timer));

    return { frame, drawing, failed, paint, redraw, redrawSoon };
};
