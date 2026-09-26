<script setup>
/**
 * One Neekah Kenangan album for the couple: what guests shared (gallery,
 * written and voice wishes), the QR table cards, and its settings, with the
 * guest link, the ZIP download and an upgrade to Pro beside them.
 *
 * The gallery paints at once from the last visit (sessionStorage) and then
 * asks the server again; the server answers 304 when nothing changed, so a
 * revisit costs almost nothing. The grid shows small thumbnails and the
 * viewer a display-size copy, never the original, which is only fetched to
 * download.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import CameraPrintDesigner from './CameraPrintDesigner.vue';
import { t } from '../../i18n.js';

const props = defineProps({
    album: { type: Object, required: true },
    upgrade: { type: Object, default: null },
    limits: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const TABS = ['galeri', 'ucapan', 'kad', 'tetapan'];
const tab = ref(TABS.includes(window.location.hash.slice(1)) ? window.location.hash.slice(1) : (Object.keys(props.errors).length ? 'tetapan' : 'galeri'));
watch(tab, (value) => history.replaceState(history.state, '', `#${value}`));

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest', ...(options.headers ?? {}) },
    });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    return response.json();
};

/* ---------------------------------------------------------------- Gallery */

const media = ref([]);
const next = ref(null);
const loadingMedia = ref(false);
const firstLoad = ref(true);
const filter = ref('');
const selecting = ref(false);
const selected = ref(new Set());
const confirmDelete = ref(false);
const viewingIndex = ref(null);
const zipping = ref(false);

const cacheKey = () => `nk-kenangan:${props.album.id}:${filter.value || 'all'}`;
const readCache = () => {
    try {
        return JSON.parse(sessionStorage.getItem(cacheKey()) ?? 'null');
    } catch {
        return null;
    }
};
const writeCache = (items, nextId) => {
    try {
        sessionStorage.setItem(cacheKey(), JSON.stringify({ items, next: nextId }));
    } catch {
        // Private mode or a full quota: the gallery still works, just not instantly.
    }
};

const loadMedia = async (reset = false) => {
    if (loadingMedia.value) return;

    if (reset) {
        const cached = readCache();
        if (cached) {
            media.value = cached.items;
            next.value = cached.next;
            firstLoad.value = false;
        }
    }

    loadingMedia.value = true;

    try {
        const url = new URL(props.album.urls.media, window.location.origin);
        if (filter.value) url.searchParams.set('type', filter.value);
        if (!reset && next.value) url.searchParams.set('before', next.value);
        const data = await request(url);
        media.value = reset ? data.items : [...media.value, ...data.items];
        next.value = data.next;
        if (reset) writeCache(data.items, data.next);
    } finally {
        loadingMedia.value = false;
        firstLoad.value = false;
    }
};

const setFilter = (value) => {
    filter.value = value;
    media.value = [];
    firstLoad.value = true;
    loadMedia(true);
};

const toggle = (item) => {
    const set = new Set(selected.value);
    set.has(item.id) ? set.delete(item.id) : set.add(item.id);
    selected.value = set;
};
const selectAll = () => {
    selected.value = new Set(media.value.slice(0, props.limits.selected_items).map((item) => item.id));
};
const stopSelecting = () => {
    selecting.value = false;
    selected.value = new Set();
};

const deleteSelected = async () => {
    if (!confirmDelete.value) {
        confirmDelete.value = true;
        setTimeout(() => (confirmDelete.value = false), 4000);
        return;
    }

    confirmDelete.value = false;
    const ids = [...selected.value];
    await request(props.album.urls.bulk, { method: 'POST', body: JSON.stringify({ ids }) });
    media.value = media.value.filter((item) => !selected.value.has(item.id));
    writeCache(media.value, next.value);
    stopSelecting();
};

/**
 * The chosen files as one ZIP. A plain form post, so the browser shows its
 * own download progress and a phone never holds the whole ZIP in memory.
 */
const zipForm = ref(null);
const downloadSelected = () => {
    if (!selected.value.size) return;
    zipping.value = true;
    zipForm.value.submit();
    setTimeout(() => (zipping.value = false), 6000);
};

const viewing = computed(() => (viewingIndex.value === null ? null : media.value[viewingIndex.value] ?? null));
const openViewer = (index) => (viewingIndex.value = index);
const step = (by) => {
    if (viewingIndex.value === null) return;
    const target = viewingIndex.value + by;
    if (target >= 0 && target < media.value.length) viewingIndex.value = target;
};
const onKey = (event) => {
    if (viewingIndex.value === null) return;
    if (event.key === 'Escape') viewingIndex.value = null;
    if (event.key === 'ArrowRight') step(1);
    if (event.key === 'ArrowLeft') step(-1);
};

// Warm the neighbours so stepping through the viewer is instant.
watch(viewingIndex, (index) => {
    if (index === null) return;
    [index - 1, index + 1].forEach((at) => {
        const item = media.value[at];
        if (item?.type === 'photo') new Image().src = item.display;
    });
});

/* ----------------------------------------------------------------- Wishes */

const wishes = ref([]);
const wishesNext = ref(null);
const wishFilter = ref('');
const loadingWishes = ref(false);
const wishesLoaded = ref(false);
const confirmingWish = ref(null);

const loadWishes = async (reset = false) => {
    if (loadingWishes.value) return;
    loadingWishes.value = true;

    try {
        const url = new URL(props.album.urls.wishes, window.location.origin);
        if (wishFilter.value) url.searchParams.set('type', wishFilter.value);
        if (!reset && wishesNext.value) url.searchParams.set('before', wishesNext.value);
        const data = await request(url);
        wishes.value = reset ? data.items : [...wishes.value, ...data.items];
        wishesNext.value = data.next;
    } finally {
        loadingWishes.value = false;
        wishesLoaded.value = true;
    }
};

const setWishFilter = (value) => {
    wishFilter.value = value;
    loadWishes(true);
};

const deleteWish = async (wish) => {
    if (confirmingWish.value !== wish.id) {
        confirmingWish.value = wish.id;
        setTimeout(() => (confirmingWish.value = null), 4000);
        return;
    }

    confirmingWish.value = null;
    await request(wish.delete_url, { method: 'DELETE' });
    wishes.value = wishes.value.filter((item) => item.id !== wish.id);
};

watch(tab, (value) => value === 'ucapan' && !wishesLoaded.value && loadWishes(true), { immediate: true });

/* ------------------------------------------------------------------ Share */

const copied = ref(false);
const copy = async () => {
    try {
        await navigator.clipboard.writeText(props.album.url);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        // Older browsers: the link is selectable on screen anyway.
    }
};
const whatsappUrl = computed(() => `https://wa.me/?text=${encodeURIComponent(`${t('camera.share_message')} ${props.album.url}`)}`);

const ringgit = (amount) => `RM${Number(amount).toLocaleString('en-MY', { maximumFractionDigits: 2 })}`;
/** "340 MB" below a gigabyte, "1.4 GB" above. */
const storage = (bytes) => (bytes < 1024 ** 3 ? { value: Math.max(0, Math.round(bytes / 1024 ** 2)), unit: 'MB' } : { value: (bytes / 1024 ** 3).toFixed(1), unit: 'GB' });
const photoShare = computed(() => (props.album.max_photos ? Math.min(100, Math.round((props.album.photos / props.album.max_photos) * 100)) : null));
const seconds = (value) => `${Math.floor(value / 60)}:${String(value % 60).padStart(2, '0')}`;

onMounted(() => {
    loadMedia(true);
    window.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="flex min-w-0 flex-col gap-6">
        <a :href="album.urls.index" class="self-start text-sm font-medium text-ink-muted hover:text-brand-700">← {{ $t('camera.all_albums') }}</a>

        <p v-if="errors.tier" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ errors.tier }}</p>
        <p v-if="errors.ids" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ errors.ids }}</p>

        <div v-if="!album.active" class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            {{ album.purged ? $t('camera.album_purged_notice', { date: album.expires }) : $t('camera.album_ended_notice') }}
        </div>

        <!-- The album at a glance. -->
        <section class="relative overflow-hidden rounded-3xl border border-gold-300/60 bg-linear-to-br from-brand-50 via-ivory to-ivory-deep p-5 sm:p-7">
            <div class="pointer-events-none absolute -top-20 -right-10 size-64 rounded-full bg-brand-100/60 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex min-w-0 flex-col gap-5">
                <div class="flex min-w-0 flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-[0.25em] text-gold-600 uppercase">{{ $t('camera.brand') }}</p>
                        <h2 class="mt-1 font-display text-2xl font-semibold break-words sm:text-3xl">{{ album.title }}</h2>
                        <p class="text-sm text-ink-muted">{{ album.date }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span :class="['rounded-full px-3 py-1 text-xs font-semibold', album.tier === 'pro' ? 'bg-ink text-white' : 'bg-surface-raised text-ink ring-1 ring-line']">{{ album.tier_label }}</span>
                        <span v-if="album.active" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">{{ $t('camera.kept_until', { date: album.expires }) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-2xl bg-surface-raised/90 p-4 ring-1 ring-line/70">
                        <p class="text-xs text-ink-muted">{{ $t('camera.photos') }}</p>
                        <p class="font-display text-2xl font-semibold">{{ album.photos }}<span v-if="album.max_photos" class="text-sm text-ink-muted"> / {{ album.max_photos }}</span></p>
                        <div v-if="photoShare !== null" class="mt-2 h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-brand-600" :style="{ width: `${photoShare}%` }"></div></div>
                    </div>
                    <div class="rounded-2xl bg-surface-raised/90 p-4 ring-1 ring-line/70">
                        <p class="text-xs text-ink-muted">{{ $t('camera.videos') }}</p>
                        <p class="font-display text-2xl font-semibold">{{ album.videos }}</p>
                    </div>
                    <div class="rounded-2xl bg-surface-raised/90 p-4 ring-1 ring-line/70">
                        <p class="text-xs text-ink-muted">{{ $t('camera.wishes') }}</p>
                        <p class="font-display text-2xl font-semibold">{{ album.wishes }}</p>
                    </div>
                    <div class="rounded-2xl bg-surface-raised/90 p-4 ring-1 ring-line/70">
                        <p class="text-xs text-ink-muted">{{ $t('camera.storage') }}</p>
                        <p class="font-display text-2xl font-semibold">{{ storage(album.bytes).value }} <span class="text-sm text-ink-muted">{{ storage(album.bytes).unit }}</span></p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <!-- Everything guests left, the cards and the settings. -->
            <div class="flex min-w-0 flex-col gap-5">
                <nav class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1" :aria-label="$t('camera.sections')">
                    <button
                        v-for="name in TABS"
                        :key="name"
                        v-show="name !== 'kad' || album.print"
                        type="button"
                        :class="['shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition', tab === name ? 'bg-ink text-white shadow-sm' : 'text-ink-muted hover:bg-surface-muted hover:text-ink']"
                        @click="tab = name"
                    >{{ $t(`camera.tab_${name}`) }}</button>
                </nav>

                <!-- Gallery -->
                <section v-show="tab === 'galeri'" class="flex min-w-0 flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-4 sm:p-6">
                    <div class="flex min-w-0 flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="option in ['', 'photo', 'video']" :key="option" type="button" :class="['rounded-full border px-3 py-1.5 text-xs font-medium transition', filter === option ? 'border-brand-500 bg-brand-50 text-brand-800' : 'border-line text-ink-muted hover:border-brand-300']" @click="setFilter(option)">{{ $t(`camera.filter_${option || 'all'}`) }}</button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template v-if="selecting">
                                <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium" @click="selectAll">{{ $t('camera.select_all') }}</button>
                                <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium" @click="stopSelecting">{{ $t('camera.done_selecting') }}</button>
                            </template>
                            <button v-else type="button" :disabled="!media.length" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium disabled:opacity-40" @click="selecting = true">{{ $t('camera.select') }}</button>
                        </div>
                    </div>

                    <!-- What to do with the chosen ones. -->
                    <div v-if="selecting" class="sticky top-2 z-10 flex min-w-0 flex-wrap items-center justify-between gap-3 rounded-2xl bg-ink px-4 py-3 text-sm text-white shadow-lg">
                        <span>{{ selected.size ? $t('camera.selected', { count: selected.size }) : $t('camera.tap_to_select') }}</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" :disabled="!selected.size || zipping" class="rounded-full bg-white px-4 py-2 text-xs font-semibold text-ink disabled:opacity-40" @click="downloadSelected">{{ zipping ? $t('camera.zip_preparing') : $t('camera.download_selected') }}</button>
                            <button v-if="album.active" type="button" :disabled="!selected.size" class="rounded-full border border-white/40 px-4 py-2 text-xs font-semibold disabled:opacity-40" @click="deleteSelected">{{ confirmDelete ? $t('camera.confirm_delete_many', { count: selected.size }) : $t('camera.delete_selected') }}</button>
                        </div>
                    </div>
                    <form ref="zipForm" :action="album.urls.export_selected" method="POST" class="hidden">
                        <input type="hidden" name="_token" :value="csrf">
                        <input v-for="id in selected" :key="id" type="hidden" name="ids[]" :value="id">
                    </form>

                    <ul v-if="firstLoad" class="grid grid-cols-3 gap-1.5 sm:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6" aria-hidden="true">
                        <li v-for="n in 12" :key="n" class="aspect-square animate-pulse rounded-xl bg-surface-muted"></li>
                    </ul>
                    <div v-else-if="!media.length" class="flex flex-col items-center gap-2 rounded-2xl bg-surface-muted px-6 py-12 text-center">
                        <span class="text-4xl" aria-hidden="true">📷</span>
                        <p class="text-sm text-ink-muted">{{ $t('camera.gallery_empty') }}</p>
                    </div>
                    <ul v-else class="grid grid-cols-3 gap-1.5 sm:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6">
                        <li v-for="(item, index) in media" :key="item.id" class="relative min-w-0">
                            <button type="button" class="group block w-full overflow-hidden rounded-xl bg-surface-muted" @click="selecting ? toggle(item) : openViewer(index)">
                                <img v-if="item.type === 'photo'" :src="item.thumb" alt="" loading="lazy" decoding="async" :class="['aspect-square w-full object-cover transition duration-300 group-hover:scale-105', selected.has(item.id) ? 'opacity-60' : '']">
                                <span v-else class="flex aspect-square w-full flex-col items-center justify-center gap-1 bg-ink/85 text-white" aria-hidden="true"><span class="text-2xl">▶</span><span class="text-[10px] tracking-wide uppercase opacity-70">{{ $t('camera.filter_video') }}</span></span>
                            </button>
                            <span v-if="selecting" :class="['pointer-events-none absolute top-1.5 right-1.5 flex size-6 items-center justify-center rounded-full border-2 border-white text-xs text-white shadow', selected.has(item.id) ? 'bg-brand-600' : 'bg-black/30']">{{ selected.has(item.id) ? '✓' : '' }}</span>
                            <span v-if="selected.has(item.id)" class="pointer-events-none absolute inset-0 rounded-xl ring-4 ring-brand-500 ring-inset"></span>
                        </li>
                    </ul>
                    <button v-if="next" type="button" class="self-center rounded-full border border-line px-5 py-2 text-sm font-medium" :disabled="loadingMedia" @click="loadMedia()">{{ loadingMedia ? '…' : $t('camera.more') }}</button>
                </section>

                <!-- Wishes -->
                <section v-show="tab === 'ucapan'" class="flex min-w-0 flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-4 sm:p-6">
                    <div class="flex min-w-0 flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="font-display text-lg font-semibold">{{ $t('camera.wishes_title') }}</h3>
                            <p class="text-sm text-ink-muted">{{ album.allows_voice ? $t('camera.wishes_help_voice') : $t('camera.wishes_help') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="option in ['', 'text', 'voice']" :key="option" type="button" :class="['rounded-full border px-3 py-1.5 text-xs font-medium transition', wishFilter === option ? 'border-brand-500 bg-brand-50 text-brand-800' : 'border-line text-ink-muted hover:border-brand-300']" @click="setWishFilter(option)">{{ $t(`camera.wish_filter_${option || 'all'}`) }}</button>
                        </div>
                    </div>

                    <p v-if="wishesLoaded && !wishes.length" class="rounded-2xl bg-surface-muted px-6 py-12 text-center text-sm text-ink-muted">💌 {{ $t('camera.wishes_empty') }}</p>
                    <ul class="grid min-w-0 gap-3 lg:grid-cols-2">
                        <li v-for="wish in wishes" :key="wish.id" class="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface p-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-100 font-display font-semibold text-brand-700">{{ (wish.by || '?').slice(0, 1).toUpperCase() }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold">{{ wish.by || $t('camera.anonymous') }}</p>
                                    <p class="text-xs text-ink-muted">{{ wish.at }}<template v-if="wish.type === 'voice'"> · 🎙️ {{ seconds(wish.seconds || 0) }}</template></p>
                                </div>
                                <button type="button" class="shrink-0 rounded-full border border-line px-3 py-1 text-xs font-medium text-ink-muted hover:border-brand-400 hover:text-brand-700" @click="deleteWish(wish)">{{ confirmingWish === wish.id ? $t('camera.confirm_delete') : $t('camera.delete') }}</button>
                            </div>
                            <p v-if="wish.type === 'text'" class="text-sm leading-relaxed break-words whitespace-pre-line">{{ wish.message }}</p>
                            <audio v-else :src="wish.audio" controls preload="none" class="w-full"></audio>
                        </li>
                    </ul>
                    <button v-if="wishesNext" type="button" class="self-center rounded-full border border-line px-5 py-2 text-sm font-medium" :disabled="loadingWishes" @click="loadWishes()">{{ $t('camera.more') }}</button>
                </section>

                <!-- QR table cards -->
                <div v-if="album.print" v-show="tab === 'kad'" class="min-w-0">
                    <CameraPrintDesigner :print="album.print" :save-url="album.urls.design" :csrf="csrf" />
                </div>

                <!-- Settings -->
                <form v-show="tab === 'tetapan'" :action="album.urls.update" method="POST" class="flex min-w-0 flex-col gap-5 rounded-3xl border border-line bg-surface-raised p-5 sm:p-6">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="PUT">
                    <h3 class="font-display text-lg font-semibold">{{ $t('camera.settings') }}</h3>

                    <fieldset :disabled="!album.active" class="flex min-w-0 flex-col gap-5 disabled:opacity-60">
                        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                            <label class="flex min-w-0 flex-col gap-1.5">
                                <span class="text-sm font-medium">{{ $t('camera.album_name') }}</span>
                                <input name="title" :value="album.raw_title" maxlength="120" :placeholder="$t('camera.album_title_placeholder')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                                <span v-if="errors.title" class="text-xs text-brand-700">{{ errors.title }}</span>
                            </label>
                            <label class="flex min-w-0 flex-col gap-1.5">
                                <span class="text-sm font-medium">{{ $t('camera.album_date') }}</span>
                                <input name="event_date" type="date" :value="album.event_date" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                                <span class="text-xs text-ink-muted">{{ $t('camera.album_date_settings_help') }}</span>
                            </label>
                        </div>

                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium">{{ $t('camera.welcome') }}</span>
                            <textarea name="welcome_message" rows="3" maxlength="300" :placeholder="$t('camera.welcome_placeholder')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">{{ album.welcome_message }}</textarea>
                        </label>

                        <label class="flex min-w-0 flex-col gap-1.5 sm:max-w-sm">
                            <span class="text-sm font-medium">{{ album.restricted ? $t('camera.passcode_change') : $t('camera.passcode_set') }}</span>
                            <input name="passcode" type="text" minlength="4" maxlength="32" autocomplete="off" :placeholder="album.restricted ? $t('camera.passcode_keep') : $t('camera.passcode_none')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                            <span v-if="errors.passcode" class="text-xs text-brand-700">{{ errors.passcode }}</span>
                        </label>

                        <div class="flex flex-col gap-3 rounded-2xl bg-surface-muted p-4">
                            <label class="flex items-start gap-3">
                                <input type="hidden" name="guests_can_view" value="0">
                                <input type="checkbox" name="guests_can_view" value="1" :checked="album.guests_can_view" class="mt-1 accent-brand-600">
                                <span><span class="block text-sm font-medium">{{ $t('camera.guests_can_view') }}</span><span class="block text-xs text-ink-muted">{{ $t('camera.guests_can_view_help') }}</span></span>
                            </label>
                            <label class="flex items-start gap-3">
                                <input type="hidden" name="uploads_open" value="0">
                                <input type="checkbox" name="uploads_open" value="1" :checked="album.uploads_open" class="mt-1 accent-brand-600">
                                <span><span class="block text-sm font-medium">{{ $t('camera.uploads_open') }}</span><span class="block text-xs text-ink-muted">{{ $t('camera.uploads_open_help') }}</span></span>
                            </label>
                            <label v-if="album.restricted" class="flex items-start gap-3">
                                <input type="checkbox" name="remove_passcode" value="1" class="mt-1 accent-brand-600">
                                <span class="text-sm font-medium">{{ $t('camera.remove_passcode') }}</span>
                            </label>
                        </div>

                        <div><button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('camera.save') }}</button></div>
                    </fieldset>
                </form>
            </div>

            <!-- Sharing, keeping and upgrading. -->
            <aside class="flex min-w-0 flex-col gap-5 xl:sticky xl:top-6 xl:self-start">
                <section v-if="album.active" class="flex min-w-0 flex-col gap-3 rounded-3xl border border-line bg-surface-raised p-5">
                    <h3 class="font-display text-base font-semibold">{{ $t('camera.guest_link') }}</h3>
                    <input :value="album.url" readonly class="w-full min-w-0 rounded-xl border border-line bg-surface px-3 py-2.5 text-sm" @focus="$event.target.select()">
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" class="rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700" @click="copy">{{ copied ? $t('camera.copied') : $t('camera.copy_link') }}</button>
                        <a :href="whatsappUrl" target="_blank" rel="noopener" class="rounded-full bg-emerald-600 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700">WhatsApp</a>
                    </div>
                    <button v-if="album.print" type="button" class="rounded-full border border-line px-4 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="tab = 'kad'">🖨️ {{ $t('camera.print_title') }}</button>
                    <UiConfirm
                        class="self-start"
                        :action="album.urls.rotate"
                        :csrf="csrf"
                        :title="$t('camera.rotate_title')"
                        :message="$t('camera.rotate_message')"
                        :confirm-label="$t('camera.rotate_confirm')"
                        :label="$t('camera.rotate')"
                        tone="brand"
                    />
                </section>

                <section class="flex min-w-0 flex-col gap-3 rounded-3xl border border-line bg-surface-raised p-5">
                    <h3 class="font-display text-base font-semibold">{{ $t('camera.download_title') }}</h3>
                    <p class="text-sm text-ink-muted">{{ album.active ? $t('camera.download_body', { date: album.expires }) : $t('camera.download_body_ended') }}</p>
                    <p v-if="album.export.building" class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">⏳ {{ $t('camera.export_building') }}</p>
                    <ul v-else-if="album.export.parts.length" class="flex flex-col gap-2">
                        <li v-for="(url, at) in album.export.parts" :key="url"><a :href="url" class="flex items-center justify-between rounded-xl border border-line px-4 py-2.5 text-sm font-medium hover:border-brand-400"><span>{{ $t('camera.download_part', { part: at + 1, total: album.export.parts.length }) }}</span><span aria-hidden="true">⬇</span></a></li>
                    </ul>
                    <form v-if="album.active" :action="album.urls.export" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <button type="submit" class="w-full rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50" :disabled="album.export.building">{{ album.export.parts.length ? $t('camera.export_again') : $t('camera.export') }}</button>
                    </form>
                    <p v-if="album.export.at" class="text-xs text-ink-muted">{{ $t('camera.export_at', { at: album.export.at }) }}</p>
                    <p class="text-xs text-ink-muted">{{ $t('camera.download_selected_hint', { count: limits.selected_items }) }}</p>
                </section>

                <section v-if="upgrade" class="relative flex min-w-0 flex-col gap-3 overflow-hidden rounded-3xl border border-brand-300 bg-linear-to-b from-brand-50 to-surface-raised p-5">
                    <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $t('camera.upgrade') }}</p>
                    <h3 class="font-display text-lg font-semibold">{{ $t('camera.upgrade_title', { tier: upgrade.label }) }}</h3>
                    <ul class="flex flex-col gap-1.5 text-sm">
                        <li class="flex gap-2"><span class="text-brand-600">✓</span>{{ $t('camera.feature_photos_unlimited') }}</li>
                        <li v-if="upgrade.limits.allows_video" class="flex gap-2"><span class="text-brand-600">✓</span>{{ $t('camera.feature_video', { mb: upgrade.limits.video_max_megabytes, minutes: Math.round(upgrade.limits.video_max_seconds / 60) }) }}</li>
                        <li class="flex gap-2"><span class="text-brand-600">✓</span>{{ $t('camera.feature_voice') }}</li>
                        <li class="flex gap-2"><span class="text-brand-600">✓</span>{{ $t('camera.feature_full_hd') }}</li>
                    </ul>
                    <form v-if="upgrade.can_checkout" :action="upgrade.checkout_url" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="album" :value="album.id">
                        <button type="submit" name="tier" :value="upgrade.tier" class="w-full rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('camera.upgrade_for', { amount: ringgit(upgrade.amount) }) }}</button>
                    </form>
                    <p v-else class="rounded-full bg-surface-muted py-2.5 text-center text-sm text-ink-muted">{{ $t('camera.checkout_soon') }}</p>
                </section>
            </aside>
        </div>

        <!-- One item, full screen, with its neighbours a tap away. -->
        <div v-if="viewing" class="fixed inset-0 z-50 flex flex-col bg-black/95" role="dialog" aria-modal="true" @click.self="viewingIndex = null">
            <div class="flex items-center justify-between gap-3 p-4 text-sm text-white">
                <span class="min-w-0 truncate">{{ viewing.by || $t('camera.anonymous') }} · {{ viewing.at }}</span>
                <div class="flex shrink-0 gap-2">
                    <a :href="viewing.url" download class="rounded-full border border-white/40 px-3 py-1">⬇ {{ $t('camera.download_original') }}</a>
                    <button type="button" class="rounded-full border border-white/40 px-3 py-1" :aria-label="$t('camera.close')" @click="viewingIndex = null">✕</button>
                </div>
            </div>
            <div class="relative flex min-h-0 flex-1 items-center justify-center p-2" @click.self="viewingIndex = null">
                <img v-if="viewing.type === 'photo'" :key="viewing.id" :src="viewing.display" alt="" class="max-h-full max-w-full object-contain" :style="{ backgroundImage: `url(${viewing.thumb})`, backgroundSize: 'contain', backgroundRepeat: 'no-repeat', backgroundPosition: 'center' }">
                <video v-else :key="viewing.id" :src="viewing.url" controls playsinline preload="metadata" class="max-h-full max-w-full"></video>
                <button v-if="viewingIndex > 0" type="button" class="absolute top-1/2 left-2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white backdrop-blur hover:bg-white/25" :aria-label="$t('camera.previous')" @click="step(-1)">‹</button>
                <button v-if="viewingIndex < media.length - 1" type="button" class="absolute top-1/2 right-2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white backdrop-blur hover:bg-white/25" :aria-label="$t('camera.next')" @click="step(1)">›</button>
            </div>
        </div>
    </div>
</template>
