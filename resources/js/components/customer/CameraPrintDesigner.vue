<script setup>
/**
 * Table cards for Kamera Majlis: a QR guests scan to reach the album, with
 * the couple's names, the date and three steps. The QR is drawn here in the
 * browser (qrcode, error correction Q, a four-module quiet zone, always dark
 * on light so every phone reads it).
 *
 * Four designs, A6 or A5, one card per page or several on an A4 sheet.
 * Print opens a sheet the browser prints or saves as PDF; PNG draws the card
 * at print resolution for anyone printing elsewhere. The chosen design is
 * remembered for the couple.
 */
import QRCode from 'qrcode';
import { computed, onMounted, ref, watch } from 'vue';
import { t } from '../../i18n.js';

const props = defineProps({
    print: { type: Object, required: true },
    saveUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

/** Millimetres of each size, portrait. */
const SIZES = { a6: [105, 148], a5: [148, 210] };

const DESIGNS = computed(() => ({
    'ikut-kad': props.print.card
        ? { bg: props.print.card.palette.bg, ink: props.print.card.palette.ink, head: props.print.card.palette.head, accent: props.print.card.palette.acc, display: props.print.card.fonts.d, script: props.print.card.fonts.s, frame: 'double' }
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
const qrSvg = ref('');
const saved = ref(false);

const style = computed(() => DESIGNS.value[design.value] ?? DESIGNS.value.klasik);
const sheetOptions = computed(() => (size.value === 'a6' ? [1, 4] : [1, 2]));

watch(size, () => {
    if (!sheetOptions.value.includes(perSheet.value)) perSheet.value = 1;
});

const qrOptions = { errorCorrectionLevel: 'Q', margin: 4, color: { dark: '#111111', light: '#ffffff' } };

onMounted(async () => {
    qrSvg.value = await QRCode.toString(props.print.url, { ...qrOptions, type: 'svg' });
});

const escape = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** One card as markup, sized in millimetres, for the preview and the print sheet alike. */
const cardHtml = (s) => {
    const [w, h] = SIZES[size.value];
    const unit = w / 105;
    const frame = s.frame === 'double'
        ? `border:${0.6 * unit}mm double ${s.accent};`
        : s.frame === 'line' ? `border:${0.3 * unit}mm solid ${s.accent};` : `border:${0.4 * unit}mm solid ${s.accent};border-radius:${4 * unit}mm;`;
    const corners = s.frame === 'floral'
        ? ['top:2mm;left:2mm', 'top:2mm;right:2mm;transform:scaleX(-1)', 'bottom:2mm;left:2mm;transform:scaleY(-1)', 'bottom:2mm;right:2mm;transform:scale(-1,-1)']
            .map((position) => `<svg viewBox="0 0 40 40" style="position:absolute;${position};width:${14 * unit}mm;height:${14 * unit}mm" fill="${s.accent}" opacity=".55"><circle cx="8" cy="8" r="5"/><circle cx="18" cy="5" r="3"/><circle cx="5" cy="18" r="3"/><circle cx="16" cy="15" r="2.4"/></svg>`).join('')
        : '';
    const code = passcode.value.trim() ? `<p style="margin:${2 * unit}mm 0 0;font-size:${3.2 * unit}mm;letter-spacing:.08em">${escape(t('camera.print_passcode', { code: passcode.value.trim() }))}</p>` : '';

    return `<div style="position:relative;box-sizing:border-box;width:${w}mm;height:${h}mm;padding:${6 * unit}mm;background:${s.bg};color:${s.ink};font-family:'${s.display}',Georgia,serif;display:flex;flex-direction:column;align-items:center;justify-content:space-between;text-align:center;overflow:hidden">
        <div style="position:absolute;inset:${3 * unit}mm;${frame};pointer-events:none"></div>${corners}
        <div style="position:relative">
            <p style="margin:0;font-size:${3 * unit}mm;letter-spacing:.3em;text-transform:uppercase;color:${s.accent}">${escape(headline.value)}</p>
            <p style="margin:${2 * unit}mm 0 0;font-family:'${s.script}',cursive;font-size:${9 * unit}mm;line-height:1.1;color:${s.head}">${escape(props.print.title)}</p>
            <p style="margin:${1.5 * unit}mm 0 0;font-size:${3.2 * unit}mm">${escape(props.print.date)}</p>
        </div>
        <div style="position:relative;width:${48 * unit}mm;height:${48 * unit}mm;background:#fff;padding:${1.5 * unit}mm;border-radius:${2 * unit}mm">${qrSvg.value}</div>
        <div style="position:relative;font-size:${3 * unit}mm;line-height:1.5">
            <p style="margin:0">${escape(t('camera.print_steps'))}</p>${code}
            <p style="margin:${2 * unit}mm 0 0;font-size:${2.4 * unit}mm;opacity:.6">Kamera Majlis · neekah.my</p>
        </div>
    </div>`;
};

const preview = computed(() => (qrSvg.value ? cardHtml(style.value) : ''));

/** Scale the millimetre card down to fit its box on screen. */
const previewScale = computed(() => (size.value === 'a6' ? 0.62 : 0.44));

const printSheet = () => {
    const [w, h] = SIZES[size.value];
    const card = cardHtml(style.value);
    const multi = perSheet.value > 1;
    const landscape = size.value === 'a5' && multi;
    const page = multi ? (landscape ? 'A4 landscape' : 'A4') : `${w}mm ${h}mm`;
    const grid = multi
        ? `<div style="display:grid;grid-template-columns:repeat(2,${w}mm);justify-content:center;align-content:center;height:100vh">${card.repeat(perSheet.value)}</div>`
        : card;

    const sheet = window.open('', '_blank');
    if (!sheet) return;
    sheet.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${escape(props.print.title)}</title><style>@page{size:${page};margin:0}html,body{margin:0}svg{width:100%;height:100%;display:block}</style></head><body>${grid}</body></html>`);
    sheet.document.close();
    sheet.onload = () => sheet.print();
    setTimeout(() => sheet.print(), 600);
};

/** The card at 300 dpi as a PNG, drawn on a canvas. */
const downloadPng = async () => {
    const [w, h] = SIZES[size.value].map((mm) => Math.round((mm / 25.4) * 300));
    const s = style.value;
    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    const u = w / 105;

    ctx.fillStyle = s.bg;
    ctx.fillRect(0, 0, w, h);
    ctx.strokeStyle = s.accent;
    ctx.lineWidth = 1.2 * u;
    ctx.strokeRect(4 * u, 4 * u, w - 8 * u, h - 8 * u);
    if (s.frame === 'double') ctx.strokeRect(6 * u, 6 * u, w - 12 * u, h - 12 * u);

    ctx.textAlign = 'center';
    ctx.fillStyle = s.accent;
    ctx.font = `${10 * u}px '${s.display}', Georgia, serif`;
    ctx.fillText(headline.value.toUpperCase(), w / 2, 22 * u);
    ctx.fillStyle = s.head;
    ctx.font = `${30 * u}px '${s.script}', cursive`;
    ctx.fillText(props.print.title, w / 2, 48 * u, w - 20 * u);
    ctx.fillStyle = s.ink;
    ctx.font = `${11 * u}px '${s.display}', Georgia, serif`;
    ctx.fillText(props.print.date, w / 2, 62 * u);

    const qr = new Image();
    qr.src = await QRCode.toDataURL(props.print.url, { ...qrOptions, width: Math.round(56 * u) });
    await qr.decode();
    const side = 56 * u;
    ctx.drawImage(qr, (w - side) / 2, h / 2 - side / 2 + 8 * u, side, side);

    ctx.fillStyle = s.ink;
    ctx.font = `${10 * u}px '${s.display}', Georgia, serif`;
    ctx.fillText(t('camera.print_steps'), w / 2, h - 30 * u);
    if (passcode.value.trim()) ctx.fillText(t('camera.print_passcode', { code: passcode.value.trim() }), w / 2, h - 20 * u);
    ctx.globalAlpha = 0.6;
    ctx.font = `${8 * u}px '${s.display}', Georgia, serif`;
    ctx.fillText('Kamera Majlis · neekah.my', w / 2, h - 10 * u);

    const link = document.createElement('a');
    link.download = `kamera-majlis-${size.value}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
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
    <section class="flex flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
        <div>
            <h2 class="font-display text-lg font-semibold">{{ $t('camera.print_title') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ $t('camera.print_intro') }}</p>
        </div>

        <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,320px)]">
            <!-- Live preview, the same markup the print sheet uses. -->
            <div class="flex min-w-0 items-center justify-center overflow-hidden rounded-xl bg-surface-muted p-4" :style="{ minHeight: size === 'a6' ? '360px' : '400px' }">
                <div :style="{ transform: `scale(${previewScale})`, transformOrigin: 'center' }" class="shadow-xl [&_svg]:block [&_svg]:size-full" v-html="preview"></div>
            </div>

            <div class="flex min-w-0 flex-col gap-4">
                <fieldset class="flex flex-col gap-2">
                    <legend class="text-sm font-medium">{{ $t('camera.print_design') }}</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="(value, key) in DESIGNS" v-show="value" :key="key" class="relative cursor-pointer">
                            <input v-model="design" type="radio" :value="key" class="peer sr-only">
                            <span class="flex items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm peer-checked:border-brand-500 peer-checked:bg-brand-50">
                                <span v-if="value" class="size-4 shrink-0 rounded-full ring-1 ring-line" :style="{ background: value.accent }"></span>{{ $t(`camera.design_${key.replace('-', '_')}`) }}
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('camera.print_size') }}</span>
                        <select v-model="size" class="nk-select rounded-xl border border-line bg-surface px-3 py-2 pr-8 text-sm">
                            <option value="a6">A6 (10.5 × 14.8 cm)</option>
                            <option value="a5">A5 (14.8 × 21 cm)</option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('camera.print_per_sheet') }}</span>
                        <select v-model.number="perSheet" class="nk-select rounded-xl border border-line bg-surface px-3 py-2 pr-8 text-sm">
                            <option v-for="count in sheetOptions" :key="count" :value="count">{{ count === 1 ? $t('camera.print_one') : $t('camera.print_many', { count }) }}</option>
                        </select>
                    </label>
                </div>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('camera.print_headline') }}</span>
                    <input v-model="headline" maxlength="60" class="rounded-xl border border-line bg-surface px-3 py-2 text-sm">
                </label>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('camera.print_passcode_label') }}</span>
                    <input v-model="passcode" maxlength="32" autocomplete="off" :placeholder="$t('camera.print_passcode_placeholder')" class="rounded-xl border border-line bg-surface px-3 py-2 text-sm">
                </label>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="flex-1 rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700" @click="printSheet">{{ $t('camera.print_now') }}</button>
                    <button type="button" class="flex-1 rounded-full border border-line px-4 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="downloadPng">{{ $t('camera.print_png') }}</button>
                </div>
                <button type="button" class="self-start text-xs text-ink-muted underline" @click="save">{{ saved ? $t('camera.print_saved') : $t('camera.print_save') }}</button>
            </div>
        </div>
    </section>
</template>
