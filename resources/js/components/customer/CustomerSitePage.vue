<script setup>
/**
 * The invitation card editor.
 *
 * Everything on the left is an ordinary form that posts to the server; the card on
 * the right is the same component a guest opens, drawn from what is in the form this
 * second. A couple should never have to save to find out what their card looks like.
 *
 * The fifty designs are not shipped with the page — between them they carry about
 * two thousand layers. The chosen one arrives with the page, and the rest are fetched
 * from site.designs when the design shelf is opened.
 *
 * The itinerary, the contacts and the bank accounts are repeaters rather than a
 * fixed number of blank rows: a couple with three items should not scroll past
 * three empty ones, and a couple with eight should not be told they may have six.
 * The server's own limits are passed in and enforced here too.
 */
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { buildPreviewProps } from '../../card/preview.js';
import CardView from '../card/CardView.vue';
import { useUploadForm } from '../../composables/useUploadForm.js';
import UiConfirm from '../ui/UiConfirm.vue';
import UiField from '../ui/UiField.vue';
import UiTextarea from '../ui/UiTextarea.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';

const props = defineProps({
    exists: { type: Boolean, required: true },
    action: { type: String, required: true },
    domain: { type: String, required: true },
    subdomainCheckUrl: { type: String, required: true },
    designsUrl: { type: String, required: true },
    site: { type: Object, required: true },
    /** The chosen design, with its canvases, palette and faces. */
    design: { type: Object, required: true },
    designIndex: { type: Object, required: true },
    palettes: { type: Array, default: () => [] },
    paletteRoles: { type: Object, default: () => ({}) },
    fontRoles: { type: Object, default: () => ({}) },
    fontOptions: { type: Array, default: () => [] },
    widgetOptions: { type: Array, default: () => [] },
    photoSlots: { type: Object, default: () => ({}) },
    tracks: { type: Array, default: () => [] },
    labels: { type: Object, default: () => ({}) },
    limits: { type: Object, required: true },
    status: { type: Object, default: null },
    gallery: { type: Object, default: null },
    wishes: { type: Array, default: () => [] },
    rsvpSummary: { type: Object, default: null },
    imageHint: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const { uploading, percent: uploadPercent, error: uploadError, submit: submitUpload } = useUploadForm();

const form = ref({
    ...props.site,
    palette: { ...(props.site.palette ?? {}) },
    fonts: { ...(props.site.fonts ?? {}) },
    widgets: [...(props.site.widgets ?? [])],
});

const qrPreview = ref(props.site.gift_qr_url);

/** Repeaters always show one empty row, so there is something to type into. */
const rowsOf = (saved, blank) => ref(saved.length ? saved.map((row) => ({ ...row })) : [{ ...blank }]);

const itinerary = rowsOf(props.site.itinerary, { time: '', label: '' });
const contacts = rowsOf(props.site.contacts, { name: '', phone: '' });
const giftAccounts = rowsOf(props.site.gift_accounts, { bank: '', holder: '', number: '' });

const add = (rows, blank, limit) => {
    if (rows.value.length < limit) rows.value.push({ ...blank });
};

const remove = (rows, at) => {
    rows.value.splice(at, 1);
    if (!rows.value.length) rows.value.push({});
};

/*
| The design
|--------------------------------------------------------------------------
*/
const design = ref(props.design);
const designTab = ref(props.design.category ?? props.designIndex.categories[0]);
const shelf = reactive({});
const loadingShelf = ref(false);
const designOpen = ref(false);

/** One category's covers, fetched the first time that shelf is looked at. */
const loadShelf = async (category) => {
    if (shelf[category] || loadingShelf.value) return;

    loadingShelf.value = true;

    try {
        const url = new URL(props.designsUrl, window.location.origin);
        url.searchParams.set('category', category);

        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (response.ok) shelf[category] = (await response.json()).designs;
    } finally {
        loadingShelf.value = false;
    }
};

const openDesigns = async () => {
    designOpen.value = !designOpen.value;
    if (designOpen.value) await loadShelf(designTab.value);
};

watch(designTab, (category) => loadShelf(category));

/** Switching design keeps everything the couple typed; only the artwork changes. */
const chooseDesign = async (slug) => {
    form.value.template = slug;

    const url = new URL(props.designsUrl, window.location.origin);
    url.searchParams.set('slug', slug);

    const response = await fetch(url, { headers: { Accept: 'application/json' } });

    if (response.ok) design.value = (await response.json()).design;
};

const designMeta = computed(() => props.designIndex.designs.find((item) => item.slug === form.value.template));

const designsIn = (category) => props.designIndex.designs.filter((item) => item.category === category);

/*
| Colours, faces and photos
|--------------------------------------------------------------------------
*/
/** A colour the couple has not touched follows the design's own. */
const colourFor = (role) => form.value.palette[role] || design.value.palette[role];

const setColour = (role, value) => {
    form.value.palette = { ...form.value.palette, [role]: value };
};

const applyPalette = (preset) => {
    form.value.palette = { ...preset.colors };
};

const resetPalette = () => {
    form.value.palette = {};
};

const resetFonts = () => {
    form.value.fonts = {};
};

const slotPhotos = ref({ ...(props.site.photos ?? {}) });
const removedSlots = ref([]);

const slotsInUse = computed(() => design.value.photoSlots ?? []);

const pickSlotPhoto = (event, slot) => {
    const file = event.target.files?.[0];

    if (!file) return;

    slotPhotos.value = { ...slotPhotos.value, [slot]: URL.createObjectURL(file) };
    removedSlots.value = removedSlots.value.filter((key) => key !== slot);
};

const dropSlotPhoto = (slot) => {
    slotPhotos.value = { ...slotPhotos.value, [slot]: null };
    if (!removedSlots.value.includes(slot)) removedSlots.value.push(slot);
};

/*
| Sections
|--------------------------------------------------------------------------
*/
const hasWidget = (key) => form.value.widgets.includes(key);

const toggleWidget = (key) => {
    form.value.widgets = hasWidget(key) ? form.value.widgets.filter((item) => item !== key) : [...form.value.widgets, key];
};

const moveWidget = (key, by) => {
    const order = [...form.value.widgets];
    const at = order.indexOf(key);
    const to = at + by;

    if (at < 0 || to < 0 || to >= order.length) return;

    order.splice(to, 0, ...order.splice(at, 1));
    form.value.widgets = order;
};

/*
| The live preview
|--------------------------------------------------------------------------
*/
const previewProps = computed(() =>
    buildPreviewProps(design.value, { ...form.value, itinerary: itinerary.value, contacts: contacts.value, gift_accounts: giftAccounts.value }, {
        labels: props.labels,
        fontOptions: props.fontOptions,
        slotPhotos: slotPhotos.value,
        photos: props.gallery?.photos ?? [],
        wishes: props.wishes.filter((wish) => wish.public).map((wish) => ({ name: wish.name, message: wish.message })),
        giftQrUrl: qrPreview.value,
    }),
);

const previewOpen = ref(false);

/** The peek is the cover alone: enough to recognise the card, cheap to redraw. */
const previewPeekProps = computed(() => ({
    ...previewProps.value,
    canvases: previewProps.value.canvases.slice(0, 1),
    widgets: [],
    thumbnail: true,
}));

// The sheet covers the page, so the page behind it should not scroll under it.
watch(previewOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});

/*
| The web address
|--------------------------------------------------------------------------
| Checked as the couple types, against the same rules the save uses, so
| "sudah diambil" is known before they fill in everything else.
*/
const savedSubdomain = props.site.subdomain;
const addressCheck = ref({ state: 'idle', message: '', suggestions: [] });
let addressTimer = null;
let addressRequest = null;

/** Lower case, spaces to dashes, nothing a web address cannot hold. */
const tidyAddress = (value) =>
    (value || '')
        .toLowerCase()
        .replace(/[\s_]+/g, '-')
        .replace(/[^a-z0-9-]/g, '')
        .replace(/-{2,}/g, '-')
        .slice(0, 63);

const checkAddress = async (value) => {
    addressRequest?.abort();
    addressRequest = new AbortController();

    try {
        const url = new URL(props.subdomainCheckUrl, window.location.origin);
        url.searchParams.set('subdomain', value);

        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal: addressRequest.signal,
        });
        if (!response.ok) {
            addressCheck.value = { state: 'idle', message: '', suggestions: [] };
            return;
        }
        const result = await response.json();
        addressCheck.value = {
            state: result.available ? 'available' : 'taken',
            message: result.message,
            suggestions: result.suggestions,
        };
    } catch (error) {
        if (error.name !== 'AbortError') addressCheck.value = { state: 'idle', message: '', suggestions: [] };
    }
};

watch(
    () => form.value.subdomain,
    (value) => {
        const tidy = tidyAddress(value);
        if (tidy !== value) {
            form.value.subdomain = tidy;
            return;
        }

        clearTimeout(addressTimer);
        if (props.exists && tidy === savedSubdomain) {
            addressCheck.value = { state: 'idle', message: '', suggestions: [] };
            return;
        }
        if (tidy.length < 3) {
            addressCheck.value = { state: 'short', message: 'Sekurang-kurangnya 3 aksara.', suggestions: [] };
            return;
        }

        addressCheck.value = { ...addressCheck.value, state: 'checking' };
        addressTimer = setTimeout(() => checkAddress(tidy), 350);
    },
    { immediate: !props.exists },
);

onBeforeUnmount(() => {
    clearTimeout(addressTimer);
    addressRequest?.abort();
});

const previewFile = (event, target) => {
    const file = event.target.files?.[0];
    if (file) target.value = URL.createObjectURL(file);
};
</script>

<template>
    <div
        v-if="status"
        :class="[
            'mb-6 flex flex-col gap-3 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between',
            status.published ? 'border-emerald-200 bg-emerald-50' : 'border-line bg-surface-raised',
        ]"
    >
        <div class="min-w-0">
            <p class="flex items-center gap-2 text-sm font-semibold">
                <span :class="['size-2 rounded-full', status.published ? 'bg-emerald-500' : 'bg-ink-muted']"></span>
                {{ status.published ? $t('card_editor.published') : $t('card_editor.draft') }}
            </p>

            <template v-if="status.published">
                <a :href="status.url" target="_blank" rel="noopener" class="mt-1 block truncate text-sm text-brand-700 underline underline-offset-4">{{ status.url }}</a>
                <p class="mt-1 text-xs text-ink-muted">
                    {{ $t('card_editor.views', { count: status.views }) }} ·
                    <a :href="status.guests_url" class="hover:text-ink">{{ $t('card_editor.rsvp_count', { count: status.rsvp_count }) }}</a> ·
                    <a :href="status.insights_url" class="hover:text-ink">{{ $t('card_editor.statistik') }}</a>
                </p>
            </template>

            <p v-else class="mt-1 text-sm break-words text-ink-muted">{{ status.draft_note }}</p>
        </div>

        <form :action="status.publish_url" method="POST" class="shrink-0">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="published" :value="status.published ? 0 : 1">
            <button
                type="submit"
                :class="[
                    'rounded-full px-5 py-2.5 text-sm font-semibold transition',
                    status.published ? 'border border-line hover:border-brand-400' : 'bg-brand-600 text-white hover:bg-brand-700',
                ]"
            >{{ status.published ? $t('card_editor.unpublish') : $t('card_editor.publish') }}</button>
        </form>
    </div>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <form :action="action" method="POST" enctype="multipart/form-data" class="flex min-w-0 flex-col gap-8" @submit="submitUpload">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="template" :value="form.template">
            <input v-for="slot in removedSlots" :key="`removed-${slot}`" type="hidden" name="remove_photos[]" :value="slot">

            <!-- Design -->
            <section id="template" class="flex scroll-mt-24 flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold">{{ $t('card_editor.reka_bentuk') }}</h2>
                        <p class="text-sm text-ink-muted">{{ $t('card_editor.design_count', { designs: limits.designs }) }}</p>
                    </div>
                    <a :href="limits.gallery_url" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 underline underline-offset-4">{{ $t('card_editor.layari_galeri') }}</a>
                </div>

                <div class="flex flex-wrap items-center gap-3 rounded-xl border border-line bg-surface p-3">
                    <span class="flex gap-1">
                        <span v-for="colour in designMeta?.swatches ?? []" :key="colour" class="size-5 rounded-full border border-line" :style="{ background: colour }" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold">{{ designMeta?.name ?? design.name }}</span>
                        <span class="block truncate text-xs text-ink-muted">{{ designMeta?.description ?? design.description }}</span>
                    </span>
                    <button type="button" class="shrink-0 rounded-full border border-line px-4 py-1.5 text-sm font-medium transition hover:border-brand-400" @click="openDesigns()">
                        {{ designOpen ? $t('card_editor.tutup') : $t('card_editor.tukar_reka_bentuk') }}
                    </button>
                </div>

                <template v-if="designOpen">
                    <div class="no-scrollbar -mx-6 flex gap-2 overflow-x-auto px-6" role="tablist" :aria-label="$t('card_editor.kategori_reka_bentuk')">
                        <button
                            v-for="category in designIndex.categories"
                            :key="category"
                            type="button"
                            role="tab"
                            :aria-selected="designTab === category"
                            :class="[
                                'shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition',
                                designTab === category ? 'border-brand-600 bg-brand-600 text-white' : 'border-line hover:border-brand-400',
                            ]"
                            @click="designTab = category"
                        >
                            {{ category }}
                            <span :class="['ml-1 text-xs', designTab === category ? 'text-white/70' : 'text-ink-muted']">{{ designsIn(category).length }}</span>
                        </button>
                    </div>

                    <p v-if="loadingShelf && !shelf[designTab]" class="text-sm text-ink-muted">{{ $t('card_editor.memuatkan_reka_bentuk') }}</p>

                    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <li v-for="item in shelf[designTab] ?? []" :key="item.slug">
                            <button type="button" class="group block w-full text-left" @click="chooseDesign(item.slug)">
                                <span
                                    :class="[
                                        'block overflow-hidden rounded-lg border-2 transition',
                                        form.template === item.slug ? 'border-brand-600 ring-2 ring-brand-400/40' : 'border-transparent group-hover:border-brand-300',
                                    ]"
                                >
                                    <CardView v-bind="item.card" />
                                </span>
                                <span class="mt-1.5 flex items-center justify-between gap-1">
                                    <span class="truncate text-xs font-semibold">{{ item.name }}</span>
                                    <svg v-if="form.template === item.slug" class="size-4 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                            </button>
                        </li>
                    </ul>
                </template>

                <p class="text-sm text-ink-muted">{{ $t('card_editor.dipilih') }} <span class="font-semibold text-ink">{{ designMeta?.name ?? design.name }}</span> &middot; {{ $t('card_editor.change_anytime') }}</p>
            </section>

            <!-- Colours and faces -->
            <section class="flex flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $t('card_editor.warna_tulisan') }}</h2>
                        <p class="text-sm text-ink-muted">{{ $t('card_editor.setiap_reka_bentuk_ada_warnanya') }}</p>
                    </div>
                    <button type="button" class="text-sm font-medium text-brand-600 underline underline-offset-4" @click="resetPalette()">{{ $t('card_editor.kembali_ke_asal') }}</button>
                </div>

                <div class="no-scrollbar -mx-6 flex gap-2 overflow-x-auto px-6">
                    <button
                        v-for="preset in palettes"
                        :key="preset.key"
                        type="button"
                        class="flex shrink-0 items-center gap-2 rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400"
                        @click="applyPalette(preset)"
                    >
                        <span class="flex gap-0.5">
                            <span v-for="role in ['bg', 'head', 'acc']" :key="role" class="size-3 rounded-full" :style="{ background: preset.colors[role] }" />
                        </span>
                        {{ preset.label }}
                    </button>
                </div>

                <input v-for="(colour, role) in form.palette" :key="`palette-${role}`" type="hidden" :name="`palette[${role}]`" :value="colour">

                <div class="grid gap-3 sm:grid-cols-2">
                    <label v-for="(label, role) in paletteRoles" :key="role" class="flex items-center gap-3 rounded-xl border border-line bg-surface px-3 py-2">
                        <input
                            type="color"
                            :value="colourFor(role)"
                            class="size-8 shrink-0 cursor-pointer rounded border border-line bg-transparent"
                            @input="setColour(role, $event.target.value)"
                        >
                        <span class="min-w-0 flex-1 truncate text-sm">{{ label }}</span>
                        <span class="shrink-0 font-mono text-xs text-ink-muted">{{ colourFor(role) }}</span>
                    </label>
                </div>

                <div class="flex flex-wrap items-end justify-between gap-3 border-t border-line pt-4">
                    <h3 class="text-sm font-semibold">{{ $t('card_editor.tulisan') }}</h3>
                    <button type="button" class="text-sm font-medium text-brand-600 underline underline-offset-4" @click="resetFonts()">{{ $t('card_editor.kembali_ke_asal') }}</button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label v-for="(label, role) in fontRoles" :key="role" class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ label }}</span>
                        <select v-model="form.fonts[role]" :name="`fonts[${role}]`" class="nk-select rounded-xl border border-line bg-surface py-2.5 pr-10 pl-4 text-sm focus:border-brand-400 focus:outline-none">
                            <option value="">{{ design.fonts[role] }} ({{ $t('card_editor.asal') }})</option>
                            <optgroup v-for="group in fontOptions" :key="group.kind" :label="group.label">
                                <option v-for="font in group.fonts" :key="font.value" :value="font.value">{{ font.label }}</option>
                            </optgroup>
                        </select>
                    </label>
                </div>
            </section>

            <!-- Address -->
            <section id="alamat" class="flex scroll-mt-24 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <div>
                    <h2 class="font-semibold">{{ $t('card_editor.alamat_web_kad') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('card_editor.pilih_sendiri_pautan_yang_anda') }}</p>
                </div>

                <label class="flex flex-col gap-2">
                    <span class="sr-only">{{ $t('card_editor.alamat_web') }}</span>
                    <span
                        :class="[
                            'flex items-center overflow-hidden rounded-xl border bg-surface transition',
                            addressCheck.state === 'available' ? 'border-emerald-400' : addressCheck.state === 'taken' || errors.subdomain ? 'border-brand-400' : 'border-line focus-within:border-brand-400',
                        ]"
                    >
                        <span class="shrink-0 pl-4 text-sm text-ink-muted">https://</span>
                        <input v-model="form.subdomain" type="text" name="subdomain" required autocomplete="off" autocapitalize="none" spellcheck="false" class="min-w-0 flex-1 bg-transparent px-1 py-2.5 text-base font-medium focus:outline-none sm:text-sm" placeholder="aina-hakim">
                        <span class="shrink-0 border-l border-line bg-surface-muted px-3 py-2.5 text-sm text-ink-muted">.{{ domain }}</span>
                    </span>

                    <span v-if="errors.subdomain && addressCheck.state === 'idle'" class="text-xs text-brand-700">{{ errors.subdomain }}</span>
                    <span v-else-if="addressCheck.state === 'checking'" class="flex items-center gap-1.5 text-xs text-ink-muted">
                        <span class="size-3 animate-spin rounded-full border-2 border-line border-t-brand-500"></span>{{ $t('card_editor.menyemak') }}</span>
                    <span v-else-if="addressCheck.state === 'available'" class="flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <span class="break-all">{{ addressCheck.message }}</span>
                    </span>
                    <span v-else-if="addressCheck.state === 'taken' || addressCheck.state === 'short'" class="flex items-center gap-1.5 text-xs font-medium text-brand-700">
                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        {{ addressCheck.message }}
                    </span>
                    <span v-else class="text-xs text-ink-muted">{{ $t('card_editor.huruf_kecil_nombor_dan_sengkang') }}</span>
                </label>

                <div v-if="addressCheck.state === 'taken' && addressCheck.suggestions.length" class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-ink-muted">{{ $t('card_editor.masih_kosong') }}</span>
                    <button
                        v-for="suggestion in addressCheck.suggestions"
                        :key="suggestion"
                        type="button"
                        class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 transition hover:border-emerald-400"
                        @click="form.subdomain = suggestion"
                    >{{ suggestion }}</button>
                </div>
            </section>

            <!-- The couple -->
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <div>
                    <h2 class="font-semibold">{{ $t('card_editor.pengantin') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('card_editor.nama_pendek_dicetak_pada_kulit') }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UiField v-model="form.groom_name" :label="$t('card_editor.nama_pengantin_lelaki')" name="groom_name" :error="errors.groom_name" required />
                    <UiField v-model="form.bride_name" :label="$t('card_editor.nama_pengantin_perempuan')" name="bride_name" :error="errors.bride_name" required />
                    <UiField v-model="form.groom_short" :label="$t('card_editor.nama_pendek_lelaki')" name="groom_short" placeholder="Hakim" :error="errors.groom_short" />
                    <UiField v-model="form.bride_short" :label="$t('card_editor.nama_pendek_perempuan')" name="bride_short" placeholder="Aina" :error="errors.bride_short" />
                    <UiField v-model="form.groom_father" :label="$t('card_editor.bapa_pengantin_lelaki')" name="groom_father" placeholder="Ismail bin Yusof" :error="errors.groom_father" />
                    <UiField v-model="form.groom_mother" :label="$t('card_editor.ibu_pengantin_lelaki')" name="groom_mother" placeholder="Salmah binti Osman" :error="errors.groom_mother" />
                    <UiField v-model="form.bride_father" :label="$t('card_editor.bapa_pengantin_perempuan')" name="bride_father" placeholder="Zulkifli bin Hassan" :error="errors.bride_father" />
                    <UiField v-model="form.bride_mother" :label="$t('card_editor.ibu_pengantin_perempuan')" name="bride_mother" placeholder="Rohana binti Ahmad" :error="errors.bride_mother" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UiTextarea v-model="form.groom_bio" :label="$t('card_editor.tentang_pengantin_lelaki')" name="groom_bio" :rows="2" :error="errors.groom_bio" />
                    <UiTextarea v-model="form.bride_bio" :label="$t('card_editor.tentang_pengantin_perempuan')" name="bride_bio" :rows="2" :error="errors.bride_bio" />
                </div>

                <UiTextarea v-model="form.salutation" :label="$t('card_editor.kata_aluan')" name="salutation" :rows="3" :error="errors.salutation" />
                <UiTextarea v-model="form.invitation_note" :label="$t('card_editor.nota_jemputan_pilihan')" name="invitation_note" :rows="3" :placeholder="$t('card_editor.doa_dan_restu_daripada_tuan')" :error="errors.invitation_note" />
            </section>

            <!-- The majlis -->
            <section id="majlis" class="flex scroll-mt-24 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <h2 class="font-semibold">{{ $t('card_editor.majlis') }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <UiField v-model="form.event_date" :label="$t('card_editor.tarikh')" name="event_date" type="date" :error="errors.event_date" required />
                    <UiField v-model="form.starts_at" :label="$t('card_editor.mula')" name="starts_at" type="time" :error="errors.starts_at" />
                    <UiField v-model="form.ends_at" :label="$t('card_editor.tamat')" name="ends_at" type="time" :error="errors.ends_at" />
                </div>

                <UiField v-model="form.venue_name" :label="$t('card_editor.nama_tempat')" name="venue_name" :placeholder="$t('card_editor.dewan_seri_melati')" :error="errors.venue_name" />
                <UiTextarea v-model="form.venue_address" :label="$t('card_editor.alamat_penuh')" name="venue_address" :rows="2" :error="errors.venue_address" />
                <UiField v-model="form.map_url" :label="$t('card_editor.pautan_peta')" name="map_url" type="url" placeholder="https://maps.google.com/..." :error="errors.map_url" />
            </section>

            <!-- Photos the design asks for -->
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <div>
                    <h2 class="font-semibold">{{ $t('card_editor.gambar_kad') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('card_editor.reka_bentuk_ini_meminta_gambar') }}</p>
                </div>

                <p v-if="!slotsInUse.length" class="text-sm text-ink-muted">{{ $t('card_editor.reka_bentuk_ini_tiada_gambar') }}</p>

                <div v-for="slot in slotsInUse" :key="slot" class="flex flex-col gap-2 border-t border-line pt-4 first:border-0 first:pt-0">
                    <span class="text-sm font-medium">{{ photoSlots[slot] ?? slot }}</span>
                    <img v-if="slotPhotos[slot]" :src="slotPhotos[slot]" alt="" class="h-32 w-full max-w-xs rounded-xl object-cover">
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" :name="`photos[${slot}]`" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="pickSlotPhoto($event, slot)">
                        <button v-if="slotPhotos[slot]" type="button" class="text-xs font-medium text-ink-muted underline underline-offset-4 hover:text-brand-700" @click="dropSlotPhoto(slot)">{{ $t('card_editor.buang_gambar') }}</button>
                    </div>
                    <span v-if="errors[`photos.${slot}`]" class="text-xs text-brand-700">{{ errors[`photos.${slot}`] }}</span>
                </div>

                <p class="text-xs text-ink-muted">{{ imageHint }}</p>
            </section>

            <!-- Which sections the card carries -->
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <div>
                    <h2 class="font-semibold">{{ $t('card_editor.bahagian_kad') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('card_editor.bahagian_ini_muncul_selepas_tiga') }}</p>
                </div>

                <ul class="flex flex-col gap-2">
                    <li v-for="option in widgetOptions" :key="option.key" class="flex items-center gap-3 rounded-xl border border-line bg-surface px-3 py-2">
                        <label class="flex min-w-0 flex-1 items-center gap-3 text-sm">
                            <input type="checkbox" class="accent-brand-600" :checked="hasWidget(option.key)" @change="toggleWidget(option.key)">
                            <span class="min-w-0 truncate">{{ option.label }}</span>
                        </label>
                        <span v-if="hasWidget(option.key)" class="flex shrink-0 gap-1">
                            <button type="button" class="rounded-full px-2 text-ink-muted transition hover:bg-surface-muted hover:text-ink" :aria-label="$t('card_editor.naik')" @click="moveWidget(option.key, -1)">&uarr;</button>
                            <button type="button" class="rounded-full px-2 text-ink-muted transition hover:bg-surface-muted hover:text-ink" :aria-label="$t('card_editor.turun')" @click="moveWidget(option.key, 1)">&darr;</button>
                        </span>
                    </li>
                </ul>

                <input v-for="key in form.widgets" :key="`widget-${key}`" type="hidden" name="widgets[]" :value="key">
            </section>

            <!-- Itinerary -->
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <div>
                    <h2 class="font-semibold">{{ $t('card_editor.atur_cara') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('card_editor.susunan_acara_pada_hari_majlis') }}</p>
                </div>

                <div v-for="(row, at) in itinerary" :key="`itinerary-${at}`" class="grid gap-3 sm:grid-cols-[10rem_1fr_auto]">
                    <input v-model="row.time" type="text" :name="`itinerary[${at}][time]`" placeholder="11:00 pagi" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input v-model="row.label" type="text" :name="`itinerary[${at}][label]`" :placeholder="$t('card_editor.ketibaan_tetamu')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <button type="button" class="rounded-full px-3 text-ink-muted transition hover:bg-surface-muted hover:text-ink" :aria-label="$t('card_editor.buang_baris')" @click="remove(itinerary, at)">&times;</button>
                </div>

                <button v-if="itinerary.length < limits.itinerary" type="button" class="w-fit rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="add(itinerary, { time: '', label: '' }, limits.itinerary)">+ Tambah baris</button>
            </section>

            <!-- Contacts and RSVP -->
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <h2 class="font-semibold">{{ $t('card_editor.hubungi_rsvp') }}</h2>

                <div v-for="(row, at) in contacts" :key="`contact-${at}`" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                    <input v-model="row.name" type="text" :name="`contacts[${at}][name]`" :placeholder="$t('card_editor.puan_rohana')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input v-model="row.phone" type="tel" :name="`contacts[${at}][phone]`" placeholder="012-345 6789" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <button type="button" class="rounded-full px-3 text-ink-muted transition hover:bg-surface-muted hover:text-ink" :aria-label="$t('card_editor.buang_baris_2')" @click="remove(contacts, at)">&times;</button>
                </div>

                <button v-if="contacts.length < limits.contacts" type="button" class="w-fit rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="add(contacts, { name: '', phone: '' }, limits.contacts)">+ Tambah nombor</button>

                <label class="flex items-center gap-2 border-t border-line pt-4 text-sm">
                    <input type="hidden" name="rsvp_enabled" value="0">
                    <input v-model="form.rsvp_enabled" type="checkbox" name="rsvp_enabled" value="1" class="accent-brand-600">{{ $t('card_editor.benarkan_tetamu_mengesahkan_kehadiran_rsvp') }}</label>

                <UiField v-model="form.rsvp_deadline" class="sm:w-56" :label="$t('card_editor.tarikh_akhir_rsvp_pilihan')" name="rsvp_deadline" type="date" :error="errors.rsvp_deadline" />
                <UiTextarea v-model="form.closing_note" :label="$t('card_editor.nota_penutup')" name="closing_note" :rows="2" :error="errors.closing_note" />
            </section>

            <!-- Gift -->
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <div>
                    <h2 class="font-semibold">{{ $t('card_editor.salam_kaut') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('card_editor.kod_qr_duitnow_dan_nombor') }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="gift_enabled" value="0">
                    <input v-model="form.gift_enabled" type="checkbox" name="gift_enabled" value="1" class="accent-brand-600">{{ $t('card_editor.papar_bahagian_hadiah_pada_kad') }}</label>

                <UiTextarea v-model="form.gift_note" :label="$t('card_editor.nota_hadiah_pilihan')" name="gift_note" :rows="2" :error="errors.gift_note" />

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('card_editor.kod_qr_duitnow') }}</span>
                    <input type="file" name="gift_qr_image" accept="image/jpeg,image/png,image/webp" class="text-sm" @change="previewFile($event, qrPreview)">
                    <img v-if="qrPreview" :src="qrPreview" alt="Kod QR DuitNow" class="mt-2 w-32 rounded-xl border border-line">
                    <span v-if="errors.gift_qr_image" class="text-xs text-brand-700">{{ errors.gift_qr_image }}</span>
                </label>

                <div v-for="(row, at) in giftAccounts" :key="`gift-${at}`" class="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto]">
                    <input v-model="row.bank" type="text" :name="`gift_accounts[${at}][bank]`" :placeholder="$t('card_editor.maybank')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input v-model="row.holder" type="text" :name="`gift_accounts[${at}][holder]`" :placeholder="$t('card_editor.nama_pemegang_akaun')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <input v-model="row.number" type="text" :name="`gift_accounts[${at}][number]`" placeholder="1234 5678 9012" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <button type="button" class="rounded-full px-3 text-ink-muted transition hover:bg-surface-muted hover:text-ink" :aria-label="$t('card_editor.buang_baris_3')" @click="remove(giftAccounts, at)">&times;</button>
                </div>

                <button v-if="giftAccounts.length < limits.gift_accounts" type="button" class="w-fit rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="add(giftAccounts, { bank: '', holder: '', number: '' }, limits.gift_accounts)">+ Tambah akaun</button>

                <label class="flex items-center gap-2 border-t border-line pt-4 text-sm">
                    <input type="hidden" name="wishes_enabled" value="0">
                    <input v-model="form.wishes_enabled" type="checkbox" name="wishes_enabled" value="1" class="accent-brand-600">{{ $t('card_editor.papar_ucapan_tetamu_yang_anda') }}</label>
            </section>

            <!-- Music -->
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <div>
                    <h2 class="font-semibold">{{ $t('card_editor.muzik_latar') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('card_editor.muzik_bermula_apabila_tetamu_membuka') }}</p>
                </div>

                <p v-if="!tracks.length" class="text-sm text-ink-muted">{{ $t('card_editor.belum_ada_trek') }}</p>

                <template v-else>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="music_enabled" value="0">
                        <input v-model="form.music_enabled" type="checkbox" name="music_enabled" value="1" class="accent-brand-600">{{ $t('card_editor.mainkan_muzik_pada_kad') }}</label>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('card_editor.pilih_trek') }}</span>
                        <select v-model="form.music_track_id" name="music_track_id" class="nk-select rounded-xl border border-line bg-surface py-2.5 pr-10 pl-4 text-sm focus:border-brand-400 focus:outline-none">
                            <option value="">{{ $t('card_editor.tiada_muzik') }}</option>
                            <option v-for="track in tracks" :key="track.id" :value="track.id">{{ track.label }}{{ track.length ? ` · ${track.length}` : '' }}</option>
                        </select>
                    </label>

                    <audio v-if="form.music_track_id" :src="tracks.find((track) => track.id === Number(form.music_track_id))?.url" controls preload="none" class="w-full max-w-sm" />
                </template>
            </section>

            <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" :label="$t('card_editor.menyimpan_kad')" />

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                    {{ uploading ? $t('card_editor.saving') : exists ? $t('card_editor.save') : $t('card_editor.create') }}
                </button>
                <a :href="limits.preview_url" target="_blank" rel="noopener" class="rounded-full border border-line px-6 py-3 text-sm font-medium transition hover:border-brand-400">{{ $t('card_editor.pratonton') }}</a>
            </div>
        </form>

        <!-- The card itself, as it stands. Beside the form on a wide screen; on a
             phone a small floating card that opens into a sheet, because the form
             fills the screen there and a preview below it would never be seen. -->
        <aside class="hidden lg:sticky lg:top-24 lg:block">
            <h2 class="text-sm font-semibold">{{ $t('card_editor.pratonton_langsung') }}</h2>

            <div class="mt-3 overflow-hidden rounded-2xl border border-line bg-surface-muted p-3">
                <div class="max-h-[70vh] overflow-y-auto rounded-xl shadow-[0_18px_40px_-18px_rgb(0_0_0/0.35)]">
                    <CardView v-bind="previewProps" />
                </div>
                <p class="mt-2 text-xs text-ink-muted">{{ $t('card_editor.pratonton_tidak_termasuk_sampul') }}</p>
            </div>
        </aside>
    </div>

    <div class="lg:hidden">
        <button
            type="button"
            class="fixed right-4 bottom-4 z-40 w-20 overflow-hidden rounded-xl border border-line bg-surface shadow-[0_12px_30px_-10px_rgb(0_0_0/0.45)] transition active:scale-95"
            :aria-label="$t('card_editor.buka_pratonton')"
            @click="previewOpen = true"
        >
            <span class="pointer-events-none block aspect-[9/16] overflow-hidden">
                <CardView v-bind="previewPeekProps" />
            </span>
            <span class="block bg-surface-raised py-1 text-[0.6rem] font-semibold">{{ $t('card_editor.pratonton') }}</span>
        </button>

        <Teleport to="body">
            <div v-if="previewOpen" class="fixed inset-0 z-50 flex flex-col justify-end bg-black/60" @click.self="previewOpen = false">
                <div class="flex max-h-[88vh] flex-col rounded-t-3xl bg-surface-muted p-3">
                    <div class="flex items-center justify-between gap-2 px-1 pb-2">
                        <h2 class="text-sm font-semibold">{{ $t('card_editor.pratonton_langsung') }}</h2>
                        <button type="button" class="rounded-full border border-line px-3 py-1 text-xs font-medium" @click="previewOpen = false">{{ $t('card_editor.tutup') }}</button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto rounded-xl shadow-[0_18px_40px_-18px_rgb(0_0_0/0.35)]">
                        <CardView v-bind="previewProps" />
                    </div>

                    <p class="px-1 pt-2 text-xs text-ink-muted">{{ $t('card_editor.pratonton_tidak_termasuk_sampul') }}</p>
                </div>
            </div>
        </Teleport>
    </div>

    <section v-if="gallery" class="mt-10 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">{{ $t('card_editor.galeri_gambar') }}</h2>

        <form :action="gallery.store_url" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5" @submit="submitUpload">
            <input type="hidden" name="_token" :value="csrf">
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required class="text-sm">
            <input type="text" name="caption" :placeholder="$t('card_editor.kapsyen_pilihan')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            <span class="text-xs text-ink-muted">{{ imageHint }}</span>

            <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" />

            <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                {{ uploading ? $t('common.memuat_naik') : $t('card_editor.muat_naik_gambar') }}
            </button>
        </form>

        <ul v-if="gallery.photos.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <li v-for="photo in gallery.photos" :key="photo.url" class="flex flex-col gap-1.5">
                <img :src="photo.url" :alt="photo.caption || ''" loading="lazy" class="h-32 w-full rounded-xl object-cover">
                <UiConfirm
                    :action="photo.destroy_url"
                    method="DELETE"
                    tone="danger"
                    :title="$t('card_editor.padam_gambar_ini')"
                    :message="$t('card_editor.gambar_akan_dibuang_dari_galeri')"
                    :confirm-label="$t('card_editor.padam')"
                    trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                    :csrf="csrf"
                >{{ $t('card_editor.padam_2') }}</UiConfirm>
            </li>
        </ul>
    </section>

    <section v-if="wishes.length" class="mt-10 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">{{ $t('card_editor.ucapan_tetamu') }}</h2>
        <p class="text-sm text-ink-muted">{{ $t('card_editor.hanya_ucapan_yang_anda_luluskan') }}</p>

        <ul class="flex flex-col gap-3">
            <li v-for="wish in wishes" :key="wish.id" class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-ink-muted italic">“{{ wish.message }}”</p>
                    <p class="mt-1 text-sm font-medium">{{ wish.name }}</p>
                </div>

                <form :action="wish.update_url" method="POST" class="shrink-0">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="approve_message" :value="wish.public ? 0 : 1">
                    <button
                        type="submit"
                        :class="[
                            'rounded-full px-5 py-2 text-xs font-semibold transition',
                            wish.public ? 'border border-line hover:border-brand-400' : 'bg-brand-600 text-white hover:bg-brand-700',
                        ]"
                    >{{ wish.public ? 'Sembunyikan' : 'Luluskan' }}</button>
                </form>
            </li>
        </ul>
    </section>

    <section v-if="rsvpSummary" class="mt-10 flex flex-col items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5">
        <h2 class="font-display text-xl font-semibold">{{ rsvpSummary.count }} jawapan RSVP diterima</h2>
        <p class="text-sm text-ink-muted">{{ $t('card_editor.lihat_siapa_yang_menjawab_siapa') }}</p>
        <a :href="rsvpSummary.url" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('card_editor.buka_senarai_tetamu') }}</a>
    </section>
</template>
