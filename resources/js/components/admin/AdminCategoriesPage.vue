<script setup>
/**
 * Categories decide what the marketplace offers and in what order, so this page
 * is a single unpaged list an admin can drag: order is only meaningful while
 * every category is on screen at once. Editing happens in the panel beside it
 * (a sheet from the bottom on a phone), because a category carries a name per
 * language, examples, a picture and a status, which is more than fits in a row.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AdminStatRow from './AdminStatRow.vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiField from '../ui/UiField.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';
import { useUploadForm } from '../../composables/useUploadForm.js';

const props = defineProps({
    categories: { type: Array, required: true },
    locales: { type: Array, required: true },
    stats: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    orderUrl: { type: String, required: true },
    imageHint: { type: String, required: true },
    /** What a form that failed validation had typed, and which category it was. */
    old: { type: Object, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const { uploading, percent, error: uploadError, submit: submitUpload } = useUploadForm();

const rows = ref([...props.categories]);
const dragging = ref(null);
const dropTarget = ref(null);
/** '', 'saving', 'saved' or 'failed' — the order saves itself, so say so. */
const orderState = ref('');

const search = ref('');
const statusFilter = ref('all');

const defaultLocale = props.locales[0];

/** null = the add form; a row = editing that category. */
const editing = ref(null);
const panelOpen = ref(false);
const form = ref(blankForm());
const removeImage = ref(false);
const previewUrl = ref(null);

const action = computed(() => (editing.value?.id ? editing.value.update_url : props.storeUrl));

/** An empty value for every language the site is served in. */
function blankPerLocale() {
    return Object.fromEntries(props.locales.map((locale) => [locale.code, '']));
}

function blankForm() {
    return { name: blankPerLocale(), icon: '', examples: blankPerLocale(), is_active: true };
}

// A form that bounced comes back open on the category it was editing, with
// what was typed — not blank, and not on some other row.
if (props.old) {
    editing.value = rows.value.find((row) => String(row.id) === String(props.old.category_id)) ?? null;
    form.value = {
        name: { ...blankPerLocale(), ...(props.old.name ?? {}) },
        icon: props.old.icon ?? '',
        examples: { ...blankPerLocale(), ...(props.old.examples ?? {}) },
        is_active: props.old.is_active,
    };
    panelOpen.value = true;
}

const counts = computed(() => ({
    all: rows.value.length,
    active: rows.value.filter((row) => row.is_active).length,
    inactive: rows.value.filter((row) => !row.is_active).length,
}));

const statusTabs = computed(() => [
    { key: 'all', label: `${counts.value.all}`, text: 'admin_categories.semua' },
    { key: 'active', label: `${counts.value.active}`, text: 'admin_categories.aktif' },
    { key: 'inactive', label: `${counts.value.inactive}`, text: 'admin_categories.tidak_aktif' },
]);

const filtering = computed(() => search.value.trim() !== '' || statusFilter.value !== 'all');

const visibleRows = computed(() => {
    const term = search.value.trim().toLowerCase();

    return rows.value.filter((row) => {
        if (statusFilter.value === 'active' && !row.is_active) return false;
        if (statusFilter.value === 'inactive' && row.is_active) return false;
        if (!term) return true;

        return [...Object.values(row.names ?? {}), ...Object.values(row.examples_all ?? {}), row.slug]
            .some((value) => (value ?? '').toLowerCase().includes(term));
    });
});

/** Languages a row has no name in yet, beyond the required default one. */
const missingLocales = (row) => props.locales.filter((locale) => locale.code !== defaultLocale.code && !(row.names?.[locale.code] ?? '').trim());

/** What the preview tile shows: a picture just chosen, the current one, or the emoji. */
const preview = computed(() => {
    if (previewUrl.value) return { src: previewUrl.value, upload: true, state: 'image_new' };

    const current = editing.value;

    if (current?.illustration && !(current.has_upload && removeImage.value)) {
        return { src: current.illustration, upload: current.has_upload, state: current.has_upload ? 'image_uploaded' : 'image_builtin' };
    }

    return { src: null, upload: false, state: 'image_emoji' };
});

const resetImage = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
    removeImage.value = false;
};

const pickImage = (event) => {
    const file = event.target.files?.[0];

    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = file ? URL.createObjectURL(file) : null;
};

const openAdd = () => {
    editing.value = null;
    form.value = blankForm();
    resetImage();
    panelOpen.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.value = {
        name: { ...blankPerLocale(), ...row.names },
        icon: row.icon,
        examples: { ...blankPerLocale(), ...row.examples_all },
        is_active: row.is_active,
    };
    resetImage();
    panelOpen.value = true;
};

const closePanel = () => {
    panelOpen.value = false;
    editing.value = null;
    resetImage();
};

const onKeydown = (event) => {
    if (event.key === 'Escape' && panelOpen.value) closePanel();
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    resetImage();
});

const move = (from, to) => {
    if (to < 0 || to >= rows.value.length || from === to) return;

    const next = [...rows.value];
    next.splice(to, 0, ...next.splice(from, 1));
    rows.value = next;
    saveOrder();
};

const drop = (to) => {
    if (dragging.value !== null) move(dragging.value, to);
    dragging.value = null;
    dropTarget.value = null;
};

/** The whole list is sent at once, so a half-applied order is impossible. */
const saveOrder = async () => {
    orderState.value = 'saving';

    try {
        const response = await fetch(props.orderUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf, Accept: 'application/json' },
            body: JSON.stringify({ items: rows.value.map((row, at) => ({ id: row.id, sort_order: at })) }),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        orderState.value = 'saved';
    } catch (problem) {
        orderState.value = 'failed';
        console.error(problem);
    }
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
};
</script>

<template>
    <div class="flex min-w-0 flex-col gap-6 break-words">
        <AdminStatRow :stats="stats" columns="grid-cols-2 xl:grid-cols-4" />

        <p v-if="orderState === 'failed'" class="rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800" role="alert">{{ $t('admin_categories.order_failed') }}</p>

        <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
            <!-- The list: one white card, like every table in the admin. -->
            <section class="flex min-w-0 flex-col overflow-clip rounded-3xl border border-line bg-surface-raised shadow-sm">
                <header class="flex min-w-0 flex-col gap-4 border-b border-line p-4 sm:p-5">
                    <div class="flex min-w-0 flex-col items-start gap-3 sm:flex-row sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <h2 class="font-display text-lg font-semibold">{{ $t('admin_categories.list_title') }}</h2>
                            <p class="mt-0.5 max-w-xl text-sm text-ink-muted">{{ $t('admin_categories.list_help') }}</p>
                        </div>
                        <button type="button" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700" @click="openAdd">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            {{ $t('admin_categories.tambah_kategori') }}
                        </button>
                    </div>

                    <div v-if="rows.length" class="flex min-w-0 flex-wrap items-center gap-2">
                        <label class="relative w-full min-w-0 sm:w-64">
                            <span class="sr-only">{{ $t('admin_categories.cari') }}</span>
                            <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                            <input
                                v-model="search"
                                type="search"
                                :placeholder="$t('admin_categories.cari')"
                                class="h-9 w-full rounded-full border border-line bg-surface pr-4 pl-9 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none"
                            >
                        </label>

                        <div class="flex min-w-0 items-center gap-1 rounded-full bg-surface-muted p-1" role="group" :aria-label="$t('admin_categories.status')">
                            <button
                                v-for="tab in statusTabs"
                                :key="tab.key"
                                type="button"
                                :aria-pressed="statusFilter === tab.key"
                                :class="['inline-flex h-7 items-center gap-1.5 rounded-full px-3 text-xs font-semibold transition', statusFilter === tab.key ? 'bg-surface-raised text-ink shadow-sm' : 'text-ink-muted hover:text-ink']"
                                @click="statusFilter = tab.key"
                            >
                                {{ $t(tab.text) }}
                                <span :class="['rounded-full px-1.5 text-[11px]', statusFilter === tab.key ? 'bg-brand-50 text-brand-700' : 'bg-surface-raised/70']">{{ tab.label }}</span>
                            </button>
                        </div>

                        <p class="ml-auto text-xs text-ink-muted" aria-live="polite">
                            <span v-if="filtering">{{ $t('admin_categories.susun_dimatikan') }}</span>
                            <span v-else-if="orderState === 'saving'">{{ $t('admin_categories.order_saving') }}</span>
                            <span v-else-if="orderState === 'saved'" class="inline-flex items-center gap-1 text-emerald-700">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7" /></svg>
                                {{ $t('admin_categories.order_saved') }}
                            </span>
                        </p>
                    </div>
                </header>

                <!-- Nothing at all yet. -->
                <div v-if="!rows.length" class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                    <span class="flex size-16 items-center justify-center rounded-3xl bg-brand-50 text-brand-600" aria-hidden="true">
                        <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="2" /><rect x="14" y="3" width="7" height="7" rx="2" /><rect x="3" y="14" width="7" height="7" rx="2" /><path d="M17.5 14v7M14 17.5h7" /></svg>
                    </span>
                    <h3 class="font-display text-lg font-semibold">{{ $t('admin_categories.empty_title') }}</h3>
                    <p class="max-w-sm text-sm text-ink-muted">{{ $t('admin_categories.empty_message') }}</p>
                    <button type="button" class="mt-1 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700" @click="openAdd">{{ $t('admin_categories.tambah_kategori') }}</button>
                </div>

                <!-- A search or filter that matches nothing. -->
                <div v-else-if="!visibleRows.length" class="flex flex-col items-center gap-2 px-6 py-14 text-center">
                    <h3 class="font-semibold">{{ $t('admin_categories.no_match_title') }}</h3>
                    <p class="max-w-sm text-sm text-ink-muted">{{ $t('admin_categories.no_match_message') }}</p>
                    <button type="button" class="mt-1 rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="clearFilters">{{ $t('admin_categories.show_all') }}</button>
                </div>

                <ul v-else class="flex min-w-0 flex-col divide-y divide-line">
                    <li
                        v-for="row in visibleRows"
                        :key="row.id"
                        :draggable="!filtering"
                        :class="[
                            'group relative flex min-w-0 items-center gap-3 px-3 py-3 transition sm:gap-4 sm:px-5',
                            dragging === rows.indexOf(row) ? 'opacity-40' : '',
                            dropTarget === rows.indexOf(row) && dragging !== rows.indexOf(row) ? 'bg-brand-50/60' : '',
                            editing?.id === row.id && panelOpen ? 'bg-brand-50/50' : 'hover:bg-surface-muted/50',
                        ]"
                        @dragstart="dragging = rows.indexOf(row)"
                        @dragenter.prevent="dropTarget = rows.indexOf(row)"
                        @dragover.prevent
                        @drop.prevent="drop(rows.indexOf(row))"
                        @dragend="dragging = null; dropTarget = null"
                    >
                        <span v-if="editing?.id === row.id && panelOpen" class="absolute inset-y-0 left-0 w-1 bg-brand-500" aria-hidden="true"></span>

                        <!-- Order: a grip to drag with a mouse, arrows for a phone or a keyboard. -->
                        <div class="flex shrink-0 items-center gap-1">
                            <svg v-if="!filtering" class="hidden size-4 cursor-grab text-ink-muted/70 transition group-hover:text-ink-muted sm:block" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.6" /><circle cx="15" cy="6" r="1.6" /><circle cx="9" cy="12" r="1.6" /><circle cx="15" cy="12" r="1.6" /><circle cx="9" cy="18" r="1.6" /><circle cx="15" cy="18" r="1.6" /></svg>
                            <span class="w-6 text-center font-display text-sm font-semibold text-gold-600">{{ rows.indexOf(row) + 1 }}</span>
                        </div>

                        <span class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-ivory ring-1 ring-line sm:size-14">
                            <img v-if="row.illustration" :src="row.illustration" alt="" class="size-full object-contain p-1.5" :class="row.has_upload ? '' : 'mix-blend-multiply'" loading="lazy">
                            <span v-else class="text-2xl" aria-hidden="true">{{ row.icon }}</span>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="truncate font-semibold">{{ row.name }}</p>
                                <span v-if="!row.is_active" class="shrink-0 rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-semibold text-ink-muted">{{ $t('admin_categories.tidak_aktif') }}</span>
                                <span v-for="locale in missingLocales(row)" :key="locale.code" class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">{{ $t('admin_categories.missing_locale', { locale: locale.code.toUpperCase() }) }}</span>
                            </div>
                            <p :class="['mt-0.5 line-clamp-1 text-xs', row.examples ? 'text-ink-muted' : 'text-ink-muted/70 italic']">{{ row.examples || $t('admin_categories.tiada_contoh') }}</p>
                            <p class="mt-1 text-xs font-medium text-ink-muted sm:hidden">{{ row.vendors_label }}</p>
                        </div>

                        <div class="hidden w-28 shrink-0 flex-col items-end text-right sm:flex">
                            <span :class="['text-sm font-semibold', row.vendors_count ? '' : 'text-ink-muted']">{{ row.vendors_label }}</span>
                            <span v-if="row.vendors_count" class="text-xs text-ink-muted">{{ row.approved_label }}</span>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <div class="flex flex-col sm:flex-row">
                                <button type="button" class="flex size-7 items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-ink disabled:opacity-25 disabled:hover:bg-transparent" :disabled="filtering || rows.indexOf(row) === 0" :aria-label="$t('admin_categories.alih_ke_atas')" @click="move(rows.indexOf(row), rows.indexOf(row) - 1)">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 15 6-6 6 6" /></svg>
                                </button>
                                <button type="button" class="flex size-7 items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-ink disabled:opacity-25 disabled:hover:bg-transparent" :disabled="filtering || rows.indexOf(row) === rows.length - 1" :aria-label="$t('admin_categories.alih_ke_bawah')" @click="move(rows.indexOf(row), rows.indexOf(row) + 1)">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                                </button>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-9 items-center gap-1.5 rounded-full border border-line px-3 text-xs font-semibold transition hover:border-brand-400 hover:text-brand-700"
                                :aria-label="$t('admin_categories.edit_nama', { name: row.name })"
                                @click="openEdit(row)"
                            >
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
                                <span class="hidden sm:inline">{{ $t('admin_categories.edit') }}</span>
                            </button>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Beside the list on a wide screen; a sheet from the bottom on a phone,
                 so editing the twelfth row does not mean scrolling past all of them. -->
            <div v-if="panelOpen" class="fixed inset-0 z-40 bg-black/40 lg:hidden" aria-hidden="true" @click="closePanel"></div>

            <form
                v-if="panelOpen"
                :action="action"
                method="POST"
                enctype="multipart/form-data"
                class="fixed inset-x-0 bottom-0 z-50 flex max-h-[92dvh] min-w-0 flex-col overflow-y-auto rounded-t-3xl border border-line bg-surface-raised shadow-2xl lg:sticky lg:inset-auto lg:top-6 lg:z-auto lg:max-h-[calc(100dvh-3rem)] lg:rounded-3xl lg:shadow-sm"
                @submit="submitUpload"
            >
                <input type="hidden" name="_token" :value="csrf">
                <input v-if="editing?.id" type="hidden" name="_method" value="PUT">
                <input v-if="editing?.id" type="hidden" name="category_id" :value="editing.id">

                <!-- Header: what the category will look like, live. -->
                <div class="sticky top-0 z-10 flex items-start gap-4 border-b border-line bg-surface-raised/95 p-5 backdrop-blur">
                    <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-ivory ring-1 ring-line">
                        <img v-if="preview.src" :src="preview.src" alt="" class="size-full object-contain p-1.5" :class="preview.upload ? '' : 'mix-blend-multiply'">
                        <span v-else class="text-3xl" aria-hidden="true">{{ form.icon || '✦' }}</span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold tracking-wide text-gold-600 uppercase">{{ editing?.id ? $t('admin_categories.edit_kategori') : $t('admin_categories.tambah_kategori') }}</p>
                        <h2 class="truncate font-display text-xl font-semibold">{{ form.name[defaultLocale.code] || $t('admin_categories.new_category') }}</h2>
                        <p v-if="editing?.id" class="text-xs text-ink-muted">{{ editing.vendors_label }}<template v-if="editing.vendors_count"> · {{ editing.approved_label }}</template></p>
                    </div>
                    <button type="button" class="-mt-1 -mr-1 flex size-9 shrink-0 items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-ink" :aria-label="$t('admin_categories.tutup')" @click="closePanel">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>

                <div class="flex flex-col gap-6 p-5">
                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-3 font-display text-sm font-semibold text-gold-600">{{ $t('admin_categories.section_name') }}</legend>
                        <UiField
                            v-for="locale in locales"
                            :key="`name-${locale.code}`"
                            v-model="form.name[locale.code]"
                            :label="`${$t('admin_categories.nama_kategori')} · ${locale.label}`"
                            :name="`name[${locale.code}]`"
                            :placeholder="$t('admin_categories.kereta_pengantin')"
                            :error="errors[`name.${locale.code}`]"
                            :help="locale.code === defaultLocale.code ? $t('admin_categories.name_help_default') : $t('admin_categories.name_help_other', { locale: defaultLocale.label })"
                            :required="locale.code === defaultLocale.code"
                            maxlength="60"
                        />
                    </fieldset>

                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-3 font-display text-sm font-semibold text-gold-600">{{ $t('admin_categories.section_examples') }}</legend>
                        <UiField
                            v-for="locale in locales"
                            :key="`examples-${locale.code}`"
                            v-model="form.examples[locale.code]"
                            :label="`${$t('admin_categories.contoh')} · ${locale.label}`"
                            :name="`examples[${locale.code}]`"
                            :placeholder="$t('admin_categories.sewa_kereta_deco_kereta')"
                            :error="errors[`examples.${locale.code}`]"
                            maxlength="120"
                        />
                        <p class="text-xs text-ink-muted">{{ $t('admin_categories.examples_help') }}</p>
                    </fieldset>

                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-3 font-display text-sm font-semibold text-gold-600">{{ $t('admin_categories.section_look') }}</legend>

                        <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] items-start gap-3">
                            <UiField v-model="form.icon" :label="$t('admin_categories.ikon_emoji')" name="icon" placeholder="🚗" :error="errors.icon" required maxlength="8" />
                            <p class="pt-7 text-xs text-ink-muted">{{ $t('admin_categories.icon_help') }}</p>
                        </div>

                        <div class="flex flex-col gap-2 rounded-2xl border border-dashed border-line bg-surface p-4">
                            <span class="text-sm font-medium">{{ $t('admin_categories.gambar_kategori') }}</span>
                            <p class="text-xs text-ink-muted">{{ $t('admin_categories.image_help') }}</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="relative inline-flex cursor-pointer items-center gap-2 rounded-full border border-line bg-surface-raised px-4 py-2 text-xs font-semibold transition hover:border-brand-400 focus-within:ring-2 focus-within:ring-brand-400/40">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3" /><circle cx="9" cy="9" r="2" /><path d="m21 15-5-5L5 21" /></svg>
                                    {{ preview.upload ? $t('admin_categories.tukar_gambar') : $t('admin_categories.pilih_gambar') }}
                                    <input type="file" name="image" accept="image/*" class="sr-only" @change="pickImage">
                                </label>
                                <span class="text-xs text-ink-muted">{{ $t(`admin_categories.${preview.state}`) }}</span>
                            </div>
                            <label v-if="editing?.has_upload && !previewUrl" class="flex items-center gap-2 text-xs text-ink-muted">
                                <input type="hidden" name="remove_image" value="0">
                                <input v-model="removeImage" type="checkbox" name="remove_image" value="1" class="size-4 accent-brand-600">
                                <span>{{ $t('admin_categories.buang_gambar_yang_dimuat_naik') }}</span>
                            </label>
                            <span class="text-xs text-ink-muted">{{ imageHint }}</span>
                            <span v-if="errors.image" class="text-xs text-brand-700">{{ errors.image }}</span>
                        </div>
                    </fieldset>

                    <fieldset class="flex flex-col gap-3">
                        <legend class="mb-3 font-display text-sm font-semibold text-gold-600">{{ $t('admin_categories.section_visibility') }}</legend>
                        <label class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-line p-4 transition hover:border-brand-300">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium">{{ $t('admin_categories.active_title') }}</span>
                                <span class="mt-0.5 block text-xs text-ink-muted">{{ $t('admin_categories.active_help') }}</span>
                            </span>
                            <input type="hidden" name="is_active" value="0">
                            <input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="peer sr-only">
                            <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-line transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400/60" aria-hidden="true">
                                <span :class="['absolute left-0.5 size-5 rounded-full bg-white shadow transition', form.is_active ? 'translate-x-5' : '']"></span>
                            </span>
                        </label>
                        <a v-if="editing?.marketplace_url" :href="editing.marketplace_url" target="_blank" rel="noopener" class="self-start text-xs font-medium text-brand-700 hover:underline">{{ $t('admin_categories.lihat_di_marketplace') }} ↗</a>
                    </fieldset>

                    <UiUploadProgress :uploading="uploading" :percent="percent" />
                    <p v-if="uploadError" class="text-sm text-brand-700">{{ uploadError }}</p>
                </div>

                <div class="sticky bottom-0 mt-auto flex flex-col gap-3 border-t border-line bg-surface-raised/95 p-5 backdrop-blur">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                        <button type="button" class="rounded-full px-5 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted" @click="closePanel">{{ $t('admin_categories.batal') }}</button>
                        <button type="submit" class="flex-1 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60" :disabled="uploading">
                            {{ editing?.id ? $t('common.simpan') : $t('admin_categories.tambah_kategori') }}
                        </button>
                    </div>

                    <!-- Deleting only exists for a category nobody uses. -->
                    <template v-if="editing?.id">
                        <UiConfirm
                            v-if="editing.vendors_count === 0"
                            :action="editing.destroy_url"
                            method="DELETE"
                            tone="danger"
                            :title="$t('admin_categories.padam_kategori_nama', { name: editing.name })"
                            :message="$t('admin_categories.kategori_ini_tiada_vendor_jadi')"
                            :confirm-label="$t('admin_categories.padam_kategori')"
                            :cancel-label="$t('admin_categories.batal')"
                            trigger-class="self-center text-xs font-medium text-red-700 underline-offset-4 transition hover:underline"
                            :csrf="csrf"
                        >{{ $t('admin_categories.padam_kategori') }}</UiConfirm>
                        <p v-else class="text-center text-xs text-ink-muted">{{ $t('admin_categories.cannot_delete', { vendors: editing.vendors_label }) }}</p>
                    </template>
                </div>
            </form>

            <!-- Nothing open: say what this page is for, on a wide screen only. -->
            <aside v-else class="hidden flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-6 shadow-sm lg:sticky lg:top-6 lg:flex">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
                </span>
                <div>
                    <h2 class="font-display text-lg font-semibold">{{ $t('admin_categories.idle_title') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ $t('admin_categories.idle_message') }}</p>
                </div>
                <ul class="flex flex-col gap-3 border-t border-line pt-4 text-sm">
                    <li v-for="tip in ['tip_order', 'tip_inactive', 'tip_delete']" :key="tip" class="flex gap-3">
                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-gold-500" aria-hidden="true"></span>
                        <span class="text-ink-muted">{{ $t(`admin_categories.${tip}`) }}</span>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</template>
