<script setup>
/**
 * Table cards for the album: a QR guests scan to reach it, with the couple's
 * names, the date and three steps. The QR is drawn here in the browser
 * (qrcode, error correction Q, a four-module quiet zone, always dark on white
 * so every phone reads it).
 *
 * Four designs, A6 or A5, one card per page or several on an A4 sheet. The
 * card is one SVG (tableCard.js) and every output is that SVG: the preview
 * shows it, print puts it on a sheet with the faces inlined, and PNG draws it
 * onto a 300 dpi canvas. So what the couple sees is what they print. The
 * chosen design is remembered for the couple.
 */
import { computed, ref, watch } from 'vue';
import { t } from '../../i18n.js';
import { SIZES, loadFaces, renderCard, stylesheetFor, svgToPng } from './tableCard.js';

const props = defineProps({
    print: { type: Object, required: true },
    saveUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

/** Relative luminance of a #rgb/#rrggbb colour, or null for anything else. */
const luminance = (colour) => {
    const hex = String(colour ?? '').trim().replace(/^#/, '');
    const full = hex.length === 3 ? [...hex].map((c) => c + c).join('') : hex;
    if (!/^[0-9a-f]{6}$/i.test(full)) return null;
    const [r, g, b] = [0, 2, 4].map((at) => {
        const v = parseInt(full.slice(at, at + 2), 16) / 255;
        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

/** `preferred` when it reads on `background` (contrast 3:1 or more), else `fallback`. */
const readableOn = (background, preferred, fallback) => {
    const [bg, fg] = [luminance(background), luminance(preferred)];
    if (bg === null || fg === null) return preferred;
    const contrast = (Math.max(bg, fg) + 0.05) / (Math.min(bg, fg) + 0.05);
    return contrast >= 3 ? preferred : fallback;
};

const DESIGNS = computed(() => ({
    'ikut-kad': props.print.card
        ? {
            bg: props.print.card.palette.bg,
            // A card's body colour is meant for its light panels; on a dark
            // background it vanishes, so fall back to the heading colour.
            ink: readableOn(props.print.card.palette.bg, props.print.card.palette.ink, props.print.card.palette.head),
            head: props.print.card.palette.head,
            accent: props.print.card.palette.acc,
            display: props.print.card.fonts.d,
            script: props.print.card.fonts.s,
            frame: 'double',
        }
        : null,
    klasik: { bg: '#fffaf3', ink: '#3b2f2a', head: '#3b2f2a', accent: '#b08d57', display: 'Playfair Display', script: 'Great Vibes', frame: 'double' },
    bunga: { bg: '#fff5f7', ink: '#4a2b35', head: '#8c3b55', accent: '#d8839a', display: 'Cormorant Garamond', script: 'Parisienne', frame: 'floral' },
    minimalis: { bg: '#ffffff', ink: '#111111', head: '#111111', accent: '#111111', display: 'Montserrat', script: 'Montserrat', frame: 'line' },
}));

const options = props.print.options ?? {};
const design = ref(DESIGNS.value[props.print.design] ? props.print.design : 'klasik');
const size = ref(options.size ?? 'a6');
const perSheet = ref(Number(options.per_sheet ?? 1));
const headline = ref(options.headline ?? t('camera.print_headline_default'));
const passcode = ref('');
const preview = ref('');
const working = ref(false);
const saved = ref(false);

const style = computed(() => DESIGNS.value[design.value] ?? DESIGNS.value.klasik);
const sheetOptions = computed(() => (size.value === 'a6' ? [1, 4] : [1, 2]));

watch(size, () => {
    if (!sheetOptions.value.includes(perSheet.value)) perSheet.value = 1;
});

/** The footer line; a page without the key still prints something sensible. */
const footer = () => {
    const text = t('camera.print_footer');

    return text === 'camera.print_footer' ? 'neekah.my' : text;
};

/** Everything renderCard needs, from the current choices. */
const card = (extra = {}) => ({
    style: style.value,
    size: size.value,
    url: props.print.url,
    headline: headline.value,
    title: props.print.title,
    date: props.print.date,
    steps: t('camera.print_steps'),
    passcode: passcode.value.trim() ? t('camera.print_passcode', { code: passcode.value.trim() }) : null,
    footer: footer(),
    ...extra,
});

// Redraw on every change once the design's faces are loaded, so the text is
// measured with the real glyphs. A slower, older render never overwrites a newer one.
let generation = 0;
watch([style, size, headline, passcode], async () => {
    const mine = ++generation;
    await loadFaces(style.value);
    if (mine === generation) preview.value = renderCard(card());
}, { immediate: true });

const escape = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/**
 * Open the sheet first, while the click still counts as the user's (a window
 * opened after an await is blocked as a popup), then fill it and print once
 * its faces are ready.
 */
const printSheet = async () => {
    const sheet = window.open('', '_blank');
    if (!sheet) return;
    sheet.document.write(`<!doctype html><title>${escape(props.print.title)}</title><p style="font:14px system-ui;padding:24px">…</p>`);

    working.value = true;
    try {
        await loadFaces(style.value);
        const [w, h] = SIZES[size.value];
        const svg = renderCard(card());
        const fontCss = await stylesheetFor(style.value);
        const multi = perSheet.value > 1;
        const landscape = size.value === 'a5' && multi;
        const [pageW, pageH] = multi ? (landscape ? [297, 210] : [210, 297]) : [w, h];
        const body = multi
            ? `<div style="display:grid;grid-template-columns:repeat(2,${w}mm);justify-content:center;align-content:center;width:${pageW}mm;height:${pageH}mm">${svg.repeat(perSheet.value)}</div>`
            : svg;

        sheet.document.open();
        sheet.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${escape(props.print.title)}</title><style>${fontCss}@page{size:${pageW}mm ${pageH}mm;margin:0}html,body{margin:0}svg{display:block}</style></head><body>${body}</body></html>`);
        sheet.document.close();

        const families = [...new Set([style.value.display, style.value.script])];
        await Promise.all(families.map((family) => sheet.document.fonts?.load(`12px "${family}"`).catch(() => null)));
        await sheet.document.fonts?.ready;
        sheet.focus();
        sheet.print();
    } finally {
        working.value = false;
    }
};

/** The card at 300 dpi, drawn from the same SVG with its faces inside it. */
const downloadPng = async () => {
    working.value = true;
    try {
        await loadFaces(style.value);
        const pixels = SIZES[size.value].map((mm) => Math.round((mm / 25.4) * 300));
        const svg = renderCard(card({ pixels, fontCss: await stylesheetFor(style.value) }));

        const link = document.createElement('a');
        link.download = `neekah-${size.value}.png`;
        link.href = await svgToPng(svg, pixels);
        link.click();
    } finally {
        working.value = false;
    }
};

const save = async () => {
    await fetch(props.saveUrl, {
        method: 'PUT',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf },
        body: JSON.stringify({ design: design.value, size: size.value, per_sheet: perSheet.value, headline: headline.value }),
    });
    saved.value = true;
    setTimeout(() => (saved.value = false), 2000);
};
</script>

<template>
    <section class="flex min-w-0 flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
        <div>
            <h2 class="font-display text-lg font-semibold">{{ $t('camera.print_title') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ $t('camera.print_intro') }}</p>
        </div>

        <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,340px)] xl:grid-cols-[minmax(0,1fr)_minmax(0,380px)]">
            <!-- Live preview: the very SVG that is printed and saved as PNG. -->
            <div class="flex min-w-0 items-center justify-center rounded-xl bg-surface-muted p-4 sm:p-8">
                <div
                    :class="['w-full shadow-xl [&_svg]:block [&_svg]:h-auto [&_svg]:w-full', size === 'a6' ? 'max-w-[340px]' : 'max-w-[420px]']"
                    v-html="preview"
                ></div>
            </div>

            <div class="flex min-w-0 flex-col gap-4">
                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-2 text-sm font-medium">{{ $t('camera.print_design') }}</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="(value, key) in DESIGNS" v-show="value" :key="key" class="relative min-w-0 cursor-pointer">
                            <input v-model="design" type="radio" :value="key" class="peer sr-only">
                            <span class="flex min-w-0 items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm peer-checked:border-brand-500 peer-checked:bg-brand-50">
                                <span v-if="value" class="size-4 shrink-0 rounded-full ring-1 ring-line" :style="{ background: value.accent }"></span>
                                <span class="truncate">{{ $t(`camera.design_${key.replace('-', '_')}`) }}</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div class="grid grid-cols-2 gap-3">
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('camera.print_size') }}</span>
                        <select v-model="size" class="nk-select min-w-0 rounded-xl border border-line bg-surface px-3 py-2 pr-8 text-sm">
                            <option value="a6">A6 (10.5 × 14.8 cm)</option>
                            <option value="a5">A5 (14.8 × 21 cm)</option>
                        </select>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('camera.print_per_sheet') }}</span>
                        <select v-model.number="perSheet" class="nk-select min-w-0 rounded-xl border border-line bg-surface px-3 py-2 pr-8 text-sm">
                            <option v-for="count in sheetOptions" :key="count" :value="count">{{ count === 1 ? $t('camera.print_one') : $t('camera.print_many', { count }) }}</option>
                        </select>
                    </label>
                </div>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('camera.print_headline') }}</span>
                    <input v-model="headline" maxlength="60" class="min-w-0 rounded-xl border border-line bg-surface px-3 py-2 text-sm">
                </label>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('camera.print_passcode_label') }}</span>
                    <input v-model="passcode" maxlength="32" autocomplete="off" :placeholder="$t('camera.print_passcode_placeholder')" class="min-w-0 rounded-xl border border-line bg-surface px-3 py-2 text-sm">
                </label>

                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
                    <button type="button" class="flex-1 rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60" :disabled="working || !preview" @click="printSheet">{{ $t('camera.print_now') }}</button>
                    <button type="button" class="flex-1 rounded-full border border-line px-4 py-2.5 text-sm font-medium transition hover:border-brand-400 disabled:opacity-60" :disabled="working || !preview" @click="downloadPng">{{ $t('camera.print_png') }}</button>
                </div>
                <button type="button" class="self-start text-xs text-ink-muted underline" @click="save">{{ saved ? $t('camera.print_saved') : $t('camera.print_save') }}</button>
            </div>
        </div>
    </section>
</template>
