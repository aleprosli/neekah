/**
 * The QR table card as one standalone SVG, the single source for the
 * designer's preview, the print / Save as PDF sheet and the PNG download.
 *
 * It used to be HTML for the preview and print, and a separately hand-drawn
 * canvas for the PNG, so the three drifted apart; the print window also never
 * loaded the card's fonts and fell back to Georgia. Now every output draws
 * this SVG, laid out in millimetres (the viewBox is the card's size), with
 * text measured against the real faces so a long name shrinks instead of
 * running off the card.
 *
 * An SVG drawn as an <img> (the PNG) cannot fetch fonts, so fontFaceCss()
 * turns the self-hosted @font-face rules the page already has (vite.config.js
 * bunny(); the CSP allows no other font source) into data: URIs that travel
 * inside the SVG or the print sheet. Pure SVG, no foreignObject, so Safari
 * does not taint the canvas.
 */
import QRCode from 'qrcode';

/** Millimetres of each size, portrait. */
export const SIZES = { a6: [105, 148], a5: [148, 210] };

const FALLBACK = { display: 'Georgia, serif', script: 'cursive' };

const escapeXml = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

const quote = (family) => `"${String(family).replace(/["\\]/g, '')}"`;

/* ------------------------------------------------------------------ fonts */

let faceRules = null;

/** Every @font-face rule the page can read, by family. */
const readFaceRules = () => {
    if (faceRules) return faceRules;
    faceRules = new Map();

    for (const sheet of Array.from(document.styleSheets)) {
        let rules;
        try {
            rules = sheet.cssRules;
        } catch {
            continue;
        }

        for (const rule of Array.from(rules ?? [])) {
            if (!(rule instanceof CSSFontFaceRule)) continue;
            const family = rule.style.getPropertyValue('font-family').replace(/^["']|["']$/g, '').trim();
            const src = rule.style.getPropertyValue('src');
            const woff2 = src.match(/url\((["']?)([^"')]+\.woff2[^"')]*)\1\)/i);
            if (!family || !woff2) continue;

            const list = faceRules.get(family) ?? [];
            list.push({
                url: new URL(woff2[2], sheet.href ?? document.baseURI).href,
                weight: Number.parseInt(rule.style.getPropertyValue('font-weight'), 10) || 400,
                style: rule.style.getPropertyValue('font-style') || 'normal',
                range: rule.style.getPropertyValue('unicode-range'),
            });
            faceRules.set(family, list);
        }
    }

    return faceRules;
};

const dataUri = (blob) => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
});

const faceCache = new Map();

/**
 * @font-face CSS for a family at its regular weight, with the files inlined.
 * An empty string when the family cannot be found or fetched: the card still
 * renders, in the generic fallback.
 */
export const fontFaceCss = (family) => {
    if (!family) return Promise.resolve('');
    if (faceCache.has(family)) return faceCache.get(family);

    const pending = (async () => {
        const faces = (readFaceRules().get(family) ?? []).filter((face) => face.style === 'normal');
        if (!faces.length) return '';

        const nearest = faces.reduce((best, face) => (Math.abs(face.weight - 400) < Math.abs(best - 400) ? face.weight : best), faces[0].weight);

        try {
            const rules = await Promise.all(faces.filter((face) => face.weight === nearest).map(async (face) => {
                const response = await fetch(face.url, { credentials: 'same-origin' });
                if (!response.ok) throw new Error(`font ${response.status}`);
                const uri = await dataUri(await response.blob());

                return `@font-face{font-family:${quote(family)};font-style:normal;font-weight:400;src:url(${uri}) format("woff2");${face.range ? `unicode-range:${face.range};` : ''}}`;
            }));

            return rules.join('');
        } catch {
            return '';
        }
    })();

    faceCache.set(family, pending);

    return pending;
};

/** The @font-face CSS for every face a style draws with. */
export const stylesheetFor = async (style) => (await Promise.all([...new Set([style.display, style.script])].map(fontFaceCss))).join('');

/* ---------------------------------------------------------------- measure */

let measureContext = null;

/** Wait for the page's own copy of a face, so measuring uses the real glyphs. */
export const loadFaces = async (style) => {
    if (!document.fonts?.load) return;
    await Promise.all([style.display, style.script].map((family) => document.fonts.load(`40px ${quote(family)}`).catch(() => null)));
};

/** Width in millimetres of `text` set at `size` mm, letter-spacing in em. */
const measure = (text, family, fallback, size, spacing = 0) => {
    measureContext ??= document.createElement('canvas').getContext('2d');
    measureContext.font = `100px ${quote(family)}, ${fallback}`;
    const chars = [...text].length;

    return (measureContext.measureText(text).width / 100) * size + chars * spacing * size;
};

/**
 * The largest size from `base` down to `min` at which `text` fits `maxWidth`;
 * when even `min` is too wide, a textLength squeezes it in.
 */
const fit = (text, family, fallback, base, min, maxWidth, spacing = 0) => {
    for (let size = base; size >= min; size -= 0.1) {
        if (measure(text, family, fallback, size, spacing) <= maxWidth) return { size, squeeze: null };
    }

    return { size: min, squeeze: maxWidth };
};

/** Two lines of roughly equal length, split at the space nearest the middle. */
const splitInTwo = (text) => {
    const spaces = [...text.matchAll(/ /g)].map((match) => match.index);
    if (!spaces.length) return null;
    const middle = text.length / 2;
    const at = spaces.reduce((best, index) => (Math.abs(index - middle) < Math.abs(best - middle) ? index : best), spaces[0]);

    return [text.slice(0, at).trim(), text.slice(at + 1).trim()];
};

/* ------------------------------------------------------------------- draw */

/** The QR as one path of unit squares on a white field, `side` mm wide. */
const qrMarkup = (url, x, y, side) => {
    const { modules } = QRCode.create(url, { errorCorrectionLevel: 'Q' });
    const quiet = 4;
    const count = modules.size + quiet * 2;
    let path = '';

    for (let row = 0; row < modules.size; row++) {
        for (let column = 0; column < modules.size; column++) {
            if (modules.get(row, column)) path += `M${column + quiet} ${row + quiet}h1v1h-1z`;
        }
    }

    return `<svg x="${x}" y="${y}" width="${side}" height="${side}" viewBox="0 0 ${count} ${count}" shape-rendering="crispEdges"><rect width="${count}" height="${count}" fill="#ffffff"/><path fill="#111111" d="${path}"/></svg>`;
};

const text = (content, { x, y, family, fallback, size, fill, spacing = 0, opacity = 1, squeeze = null }) => {
    // Letter-spacing trails the last glyph too; nudge right so the line stays centred.
    const shift = spacing ? (spacing * size) / 2 : 0;
    const attributes = [
        `x="${(x + shift).toFixed(2)}"`,
        `y="${y.toFixed(2)}"`,
        'text-anchor="middle"',
        `font-family='${quote(family)}, ${fallback}'`,
        `font-size="${size.toFixed(2)}"`,
        `fill="${escapeXml(fill)}"`,
        spacing ? `letter-spacing="${(spacing * size).toFixed(2)}"` : '',
        opacity < 1 ? `fill-opacity="${opacity}"` : '',
        squeeze ? `textLength="${squeeze.toFixed(2)}" lengthAdjust="spacingAndGlyphs"` : '',
    ].filter(Boolean).join(' ');

    return `<text ${attributes}>${escapeXml(content)}</text>`;
};

const frameMarkup = (style, w, h, u) => {
    const inset = 3 * u;

    if (style.frame === 'double') {
        const inner = inset + 1.2 * u;

        return `<rect x="${inset}" y="${inset}" width="${w - inset * 2}" height="${h - inset * 2}" fill="none" stroke="${escapeXml(style.accent)}" stroke-width="${0.35 * u}"/>`
            + `<rect x="${inner}" y="${inner}" width="${w - inner * 2}" height="${h - inner * 2}" fill="none" stroke="${escapeXml(style.accent)}" stroke-width="${0.2 * u}"/>`;
    }

    if (style.frame === 'line') {
        return `<rect x="${inset}" y="${inset}" width="${w - inset * 2}" height="${h - inset * 2}" fill="none" stroke="${escapeXml(style.accent)}" stroke-width="${0.3 * u}"/>`;
    }

    // Floral: a rounded line and a posy of dots in each corner.
    const posy = '<circle cx="8" cy="8" r="5"/><circle cx="18" cy="5" r="3"/><circle cx="5" cy="18" r="3"/><circle cx="16" cy="15" r="2.4"/>';
    const scale = (14 * u) / 40;
    const edge = 2 * u;
    const corners = [
        [edge, edge, 1, 1],
        [w - edge, edge, -1, 1],
        [edge, h - edge, 1, -1],
        [w - edge, h - edge, -1, -1],
    ].map(([x, y, sx, sy]) => `<g transform="translate(${x} ${y}) scale(${sx * scale} ${sy * scale})" fill="${escapeXml(style.accent)}" fill-opacity=".55">${posy}</g>`).join('');

    return `<rect x="${inset}" y="${inset}" width="${w - inset * 2}" height="${h - inset * 2}" rx="${4 * u}" fill="none" stroke="${escapeXml(style.accent)}" stroke-width="${0.4 * u}"/>${corners}`;
};

/**
 * One card as an SVG string, `width`/`height` in millimetres unless `pixels`
 * asks for a raster size. `fontCss` is embedded when given (the PNG); the
 * preview and the print sheet provide the faces from their own document.
 *
 * @param {{ style: object, size: 'a6'|'a5', url: string, headline: string, title: string, date: string, steps: string, passcode: string|null, footer: string, fontCss?: string, pixels?: [number, number] }} card
 */
export const renderCard = (card) => {
    const { style } = card;
    const [w, h] = SIZES[card.size];
    const u = w / 105;
    const cx = w / 2;
    const display = { family: style.display, fallback: FALLBACK.display };
    const script = { family: style.script, fallback: FALLBACK.script };
    const maxWidth = w - 24 * u;
    const parts = [];

    // Top: headline, the couple, the date.
    const headline = (card.headline ?? '').toUpperCase();
    const headlineFit = fit(headline, display.family, display.fallback, 3 * u, 2 * u, maxWidth, 0.3);
    parts.push(text(headline, { x: cx, y: 17 * u, ...display, size: headlineFit.size, squeeze: headlineFit.squeeze, fill: style.accent, spacing: 0.3 }));

    let titleBottom;
    const single = fit(card.title, script.family, script.fallback, 9 * u, 6.5 * u, maxWidth);
    const lines = single.squeeze ? splitInTwo(card.title) : null;

    if (lines) {
        const size = Math.min(...lines.map((line) => fit(line, script.family, script.fallback, 7.5 * u, 4.5 * u, maxWidth).size));
        lines.forEach((line, index) => {
            const lineFit = fit(line, script.family, script.fallback, size, 4.5 * u, maxWidth);
            parts.push(text(line, { x: cx, y: 27 * u + index * size * 1.1, ...script, size, squeeze: lineFit.squeeze, fill: style.head }));
        });
        titleBottom = 27 * u + size * 1.1;
    } else {
        parts.push(text(card.title, { x: cx, y: 30 * u, ...script, size: single.size, squeeze: single.squeeze, fill: style.head }));
        titleBottom = 30 * u;
    }

    const dateY = titleBottom + 8 * u;
    const dateFit = fit(card.date, display.family, display.fallback, 3.2 * u, 2.4 * u, maxWidth);
    parts.push(text(card.date, { x: cx, y: dateY, ...display, size: dateFit.size, squeeze: dateFit.squeeze, fill: style.ink }));

    // Bottom, from the edge up: footer, passcode, steps.
    const footerY = h - 11 * u;
    parts.push(text(card.footer, { x: cx, y: footerY, ...display, size: 2.4 * u, fill: style.ink, opacity: 0.6 }));

    let stepsY = footerY - 6.5 * u;
    if (card.passcode) {
        const codeFit = fit(card.passcode, display.family, display.fallback, 3.2 * u, 2.4 * u, maxWidth, 0.08);
        parts.push(text(card.passcode, { x: cx, y: stepsY, ...display, size: codeFit.size, squeeze: codeFit.squeeze, fill: style.ink, spacing: 0.08 }));
        stepsY -= 6 * u;
    }
    const stepsFit = fit(card.steps, display.family, display.fallback, 3 * u, 2.2 * u, maxWidth);
    parts.push(text(card.steps, { x: cx, y: stepsY, ...display, size: stepsFit.size, squeeze: stepsFit.squeeze, fill: style.ink }));

    // Middle: the QR on a white tile, as large as the space between allows.
    const top = dateY + 5 * u;
    const bottom = stepsY - 5 * u;
    const tile = Math.min(51 * u, bottom - top);
    const pad = 1.5 * u;
    const tileY = top + (bottom - top - tile) / 2;
    const qr = `<rect x="${cx - tile / 2}" y="${tileY}" width="${tile}" height="${tile}" rx="${2 * u}" fill="#ffffff"/>${qrMarkup(card.url, cx - tile / 2 + pad, tileY + pad, tile - pad * 2)}`;

    const [width, height] = card.pixels ? card.pixels.map(String) : [`${w}mm`, `${h}mm`];
    const fonts = card.fontCss ? `<style>${card.fontCss}</style>` : '';

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${w} ${h}">${fonts}`
        + `<rect width="${w}" height="${h}" fill="${escapeXml(style.bg)}"/>`
        + frameMarkup(style, w, h, u)
        + qr
        + parts.join('')
        + '</svg>';
};

/** Draw an SVG string onto a canvas `pixels` wide and tall, and return a PNG data URL. */
export const svgToPng = async (svg, [width, height]) => {
    const url = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));

    try {
        const image = new Image();
        image.src = url;
        await image.decode();
        // One frame more: some engines finish the embedded faces just after decode.
        await new Promise((resolve) => requestAnimationFrame(() => resolve()));

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(image, 0, 0, width, height);

        return canvas.toDataURL('image/png');
    } finally {
        URL.revokeObjectURL(url);
    }
};
