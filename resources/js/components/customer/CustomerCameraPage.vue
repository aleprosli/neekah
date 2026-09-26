<script setup>
/**
 * Kamera Majlis for the couple: the two tiers to buy before they have an
 * album, the album's link and usage once they do, and an upgrade to Pro.
 * Buying is an ordinary form post to the checkout, which sends them to pay.
 */
import { computed, onMounted, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import CameraPrintDesigner from './CameraPrintDesigner.vue';
import { t } from '../../i18n.js';

const props = defineProps({
    canCheckout: { type: Boolean, required: true },
    checkoutUrl: { type: String, required: true },
    tiers: { type: Array, required: true },
    retentionDays: { type: Number, required: true },
    album: { type: Object, default: null },
    purchases: { type: Array, default: () => [] },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

// The couple's album grid: a page at a time, filterable, with a select mode
// for deleting many at once.
const media = ref([]);
const next = ref(null);
const loadingMedia = ref(false);
const filter = ref('');
const selecting = ref(false);
const selected = ref(new Set());
const confirmDelete = ref(false);
const viewing = ref(null);

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest', ...(options.headers ?? {}) },
    });

    return response.json();
};

const loadMedia = async (reset = false) => {
    if (!props.album || loadingMedia.value) return;
    loadingMedia.value = true;

    try {
        const url = new URL(props.album.urls.media, window.location.origin);
        if (filter.value) url.searchParams.set('type', filter.value);
        if (!reset && next.value) url.searchParams.set('before', next.value);
        const data = await request(url);
        media.value = reset ? data.items : [...media.value, ...data.items];
        next.value = data.next;
    } finally {
        loadingMedia.value = false;
    }
};

const setFilter = (value) => {
    filter.value = value;
    loadMedia(true);
};

const toggle = (item) => {
    const set = new Set(selected.value);
    set.has(item.id) ? set.delete(item.id) : set.add(item.id);
    selected.value = set;
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
    selected.value = new Set();
    selecting.value = false;
};

onMounted(() => loadMedia(true));

const ringgit = (amount) => `RM${Number(amount).toLocaleString('en-MY', { maximumFractionDigits: 2 })}`;
const gigabytes = (bytes) => (bytes / 1024 ** 3).toFixed(bytes < 1024 ** 3 ? 2 : 1);
const photoShare = computed(() => (props.album?.max_photos ? Math.min(100, Math.round((props.album.photos / props.album.max_photos) * 100)) : null));

const featuresOf = (tier) => {
    const limits = tier.limits;
    const lines = [];

    lines.push(limits.max_photos
        ? t('camera.feature_photos_capped', { count: limits.max_photos })
        : t('camera.feature_photos_unlimited'));
    lines.push(t(limits.photo_pixels >= 3000 ? 'camera.feature_full_hd' : 'camera.feature_hd'));
    lines.push(limits.allows_video
        ? t('camera.feature_video', { mb: limits.video_max_megabytes, minutes: Math.round(limits.video_max_seconds / 60) })
        : t('camera.feature_no_video'));
    lines.push(t('camera.feature_qr'));
    lines.push(t('camera.feature_passcode'));
    lines.push(t('camera.feature_retention', { days: props.retentionDays }));

    return lines;
};

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
const whatsappUrl = computed(() => (props.album ? `https://wa.me/?text=${encodeURIComponent(`${t('camera.share_message')} ${props.album.url}`)}` : null));
</script>

<template>
    <div class="flex max-w-4xl min-w-0 flex-col gap-6">
        <p v-if="errors.tier" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ errors.tier }}</p>

        <!-- The album, once bought. -->
        <section v-if="album" class="flex flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wide text-gold-600 uppercase">{{ $t('camera.your_album') }}</p>
                    <h2 class="font-display text-xl font-semibold">{{ $t('camera.tier_active', { tier: album.tier_label }) }}</h2>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">{{ $t('camera.kept_until', { date: album.expires }) }}</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-surface-muted p-4">
                    <p class="text-xs text-ink-muted">{{ $t('camera.photos') }}</p>
                    <p class="font-display text-2xl font-semibold">{{ album.photos }}<span v-if="album.max_photos" class="text-sm text-ink-muted"> / {{ album.max_photos }}</span></p>
                    <div v-if="photoShare !== null" class="mt-2 h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-brand-600" :style="{ width: `${photoShare}%` }"></div></div>
                </div>
                <div class="rounded-xl bg-surface-muted p-4">
                    <p class="text-xs text-ink-muted">{{ $t('camera.videos') }}</p>
                    <p class="font-display text-2xl font-semibold">{{ album.videos }}</p>
                </div>
                <div class="rounded-xl bg-surface-muted p-4">
                    <p class="text-xs text-ink-muted">{{ $t('camera.storage') }}</p>
                    <p class="font-display text-2xl font-semibold">{{ gigabytes(album.bytes) }} <span class="text-sm text-ink-muted">GB</span></p>
                </div>
            </div>

            <div class="flex min-w-0 flex-col gap-2">
                <p class="text-sm font-medium">{{ $t('camera.guest_link') }}</p>
                <div class="flex min-w-0 flex-col gap-2 sm:flex-row">
                    <input :value="album.url" readonly class="min-w-0 flex-1 rounded-xl border border-line bg-surface px-4 py-2.5 text-sm" @focus="$event.target.select()">
                    <div class="flex gap-2">
                        <button type="button" class="flex-1 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:flex-none" @click="copy">{{ copied ? $t('camera.copied') : $t('camera.copy_link') }}</button>
                        <a :href="whatsappUrl" target="_blank" rel="noopener" class="flex-1 rounded-full bg-emerald-600 px-5 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700 sm:flex-none">WhatsApp</a>
                    </div>
                </div>
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
            </div>
        </section>

        <CameraPrintDesigner v-if="album" :print="album.print" :save-url="album.urls.design" :csrf="csrf" />

        <!-- What guests see and whether they can add to it. -->
        <form v-if="album" :action="album.urls.update" method="POST" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">
            <h2 class="font-display text-lg font-semibold">{{ $t('camera.settings') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('camera.album_title') }}</span>
                    <input name="title" :value="album.title" maxlength="120" :placeholder="$t('camera.album_title_placeholder')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ album.restricted ? $t('camera.passcode_change') : $t('camera.passcode_set') }}</span>
                    <input name="passcode" type="text" minlength="4" maxlength="32" autocomplete="off" :placeholder="album.restricted ? $t('camera.passcode_keep') : $t('camera.passcode_none')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <span v-if="errors.passcode" class="text-xs text-brand-700">{{ errors.passcode }}</span>
                </label>
            </div>

            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('camera.welcome') }}</span>
                <textarea name="welcome_message" rows="2" maxlength="300" :placeholder="$t('camera.welcome_placeholder')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">{{ album.welcome_message }}</textarea>
            </label>

            <div class="flex flex-col gap-3">
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
        </form>

        <!-- Everything guests shared. -->
        <section v-if="album" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-display text-lg font-semibold">{{ $t('camera.gallery') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <button v-for="option in ['', 'photo', 'video']" :key="option" type="button" :class="['rounded-full border px-3 py-1.5 text-xs font-medium', filter === option ? 'border-brand-500 bg-brand-50 text-brand-800' : 'border-line text-ink-muted']" @click="setFilter(option)">{{ $t(`camera.filter_${option || 'all'}`) }}</button>
                    <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium" @click="selecting = !selecting; selected = new Set()">{{ selecting ? $t('camera.done_selecting') : $t('camera.select') }}</button>
                </div>
            </div>

            <div v-if="selecting && selected.size" class="flex items-center justify-between gap-3 rounded-xl bg-brand-50 px-4 py-3 text-sm">
                <span>{{ $t('camera.selected', { count: selected.size }) }}</span>
                <button type="button" class="rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white" @click="deleteSelected">{{ confirmDelete ? $t('camera.confirm_delete_many', { count: selected.size }) : $t('camera.delete_selected') }}</button>
            </div>

            <p v-if="!media.length && !loadingMedia" class="rounded-xl bg-surface-muted p-6 text-center text-sm text-ink-muted">{{ $t('camera.gallery_empty') }}</p>
            <ul class="grid grid-cols-3 gap-1.5 sm:grid-cols-5 lg:grid-cols-6">
                <li v-for="item in media" :key="item.id" class="relative min-w-0">
                    <button type="button" class="block w-full" @click="selecting ? toggle(item) : (viewing = item)">
                        <img v-if="item.type === 'photo'" :src="item.thumb" alt="" loading="lazy" :class="['aspect-square w-full rounded-lg object-cover', selected.has(item.id) ? 'opacity-60 ring-4 ring-brand-500' : '']">
                        <span v-else :class="['flex aspect-square w-full items-center justify-center rounded-lg bg-ink/80 text-2xl text-white', selected.has(item.id) ? 'ring-4 ring-brand-500' : '']" aria-hidden="true">▶</span>
                    </button>
                    <span v-if="selecting" :class="['pointer-events-none absolute top-1.5 right-1.5 flex size-5 items-center justify-center rounded-full border-2 border-white text-xs text-white', selected.has(item.id) ? 'bg-brand-600' : 'bg-black/30']">{{ selected.has(item.id) ? '✓' : '' }}</span>
                </li>
            </ul>
            <button v-if="next" type="button" class="self-center rounded-full border border-line px-5 py-2 text-sm font-medium" :disabled="loadingMedia" @click="loadMedia()">{{ $t('camera.more') }}</button>
        </section>

        <!-- Keep everything before it is deleted. -->
        <section v-if="album" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <h2 class="font-display text-lg font-semibold">{{ $t('camera.download_title') }}</h2>
            <p class="text-sm text-ink-muted">{{ $t('camera.download_body', { date: album.expires }) }}</p>
            <p v-if="album.export.building" class="text-sm font-medium text-amber-800">{{ $t('camera.export_building') }}</p>
            <ul v-else-if="album.export.parts.length" class="flex flex-wrap gap-2">
                <li v-for="(url, at) in album.export.parts" :key="url"><a :href="url" class="inline-flex rounded-full border border-line px-4 py-2 text-sm font-medium hover:border-brand-400">{{ $t('camera.download_part', { part: at + 1, total: album.export.parts.length }) }}</a></li>
            </ul>
            <form :action="album.urls.export" method="POST">
                <input type="hidden" name="_token" :value="csrf">
                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="album.export.building">{{ album.export.parts.length ? $t('camera.export_again') : $t('camera.export') }}</button>
            </form>
            <p v-if="album.export.at" class="text-xs text-ink-muted">{{ $t('camera.export_at', { at: album.export.at }) }}</p>
        </section>

        <!-- One item, full screen. -->
        <div v-if="viewing" class="fixed inset-0 z-50 flex flex-col bg-black/95" @click.self="viewing = null">
            <div class="flex items-center justify-between gap-3 p-4 text-sm text-white">
                <span class="truncate">{{ viewing.by || '' }} · {{ viewing.at }}</span>
                <button type="button" class="rounded-full border border-white/40 px-3 py-1" @click="viewing = null">✕</button>
            </div>
            <div class="flex min-h-0 flex-1 items-center justify-center p-2">
                <img v-if="viewing.type === 'photo'" :src="viewing.url" alt="" class="max-h-full max-w-full object-contain">
                <video v-else :src="viewing.url" controls playsinline class="max-h-full max-w-full"></video>
            </div>
        </div>

        <!-- The tiers: to buy, or to upgrade to. -->
        <section class="flex flex-col gap-4">
            <div v-if="!album">
                <h2 class="font-display text-xl font-semibold">{{ $t('camera.pitch_title') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $t('camera.pitch_body') }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="tier in tiers"
                    :key="tier.value"
                    :class="[
                        'flex flex-col gap-4 rounded-2xl border p-5 sm:p-6',
                        tier.value === 'pro' ? 'border-brand-300 bg-brand-50/60' : 'border-line bg-surface-raised',
                    ]"
                >
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="font-display text-lg font-semibold">{{ tier.label }}</h3>
                        <p class="font-display text-2xl font-semibold">{{ ringgit(tier.price) }}</p>
                    </div>
                    <ul class="flex flex-1 flex-col gap-2 text-sm">
                        <li v-for="line in featuresOf(tier)" :key="line" class="flex gap-2"><span class="text-brand-600" aria-hidden="true">✓</span><span>{{ line }}</span></li>
                    </ul>

                    <p v-if="tier.owned" class="rounded-full bg-emerald-50 py-2.5 text-center text-sm font-medium text-emerald-800">{{ $t('camera.owned') }}</p>
                    <form v-else-if="canCheckout" :action="checkoutUrl" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="tier" :value="tier.value">
                        <button type="submit" class="w-full rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                            {{ album ? $t('camera.upgrade_for', { amount: ringgit(tier.payable) }) : $t('camera.buy_for', { amount: ringgit(tier.payable) }) }}
                        </button>
                    </form>
                    <p v-else class="rounded-full bg-surface-muted py-2.5 text-center text-sm text-ink-muted">{{ $t('camera.checkout_soon') }}</p>
                </article>
            </div>

            <p class="text-xs text-ink-muted">{{ $t('camera.payment_note', { days: retentionDays }) }}</p>
        </section>

        <section v-if="purchases.length" class="rounded-2xl border border-line">
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">{{ $t('camera.receipts') }}</h2>
            <ul class="divide-y divide-line text-sm">
                <li v-for="purchase in purchases" :key="purchase.reference" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3">
                    <span class="font-mono text-xs">{{ purchase.reference }}</span>
                    <span>{{ purchase.tier }}</span>
                    <span class="ml-auto font-medium">{{ purchase.amount }}</span>
                    <span class="text-ink-muted">{{ purchase.paid_at }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
