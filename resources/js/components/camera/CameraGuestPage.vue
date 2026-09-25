<script setup>
/**
 * Kamera Majlis for a wedding guest, opened from the QR on their table.
 *
 * Three big buttons (take a photo, pick from the phone, and on Pro record a
 * video), an upload list with progress, and the album itself. A passcode
 * screen first when the couple set one; the guest's name is asked once and
 * shown to the couple next to what they shared.
 */
import { computed, onMounted, ref } from 'vue';
import { postJson, useCameraUploads } from '../../composables/useCameraUploads.js';
import { t } from '../../i18n.js';

const props = defineProps({
    album: { type: Object, required: true },
    entered: { type: Boolean, required: true },
    name: { type: String, default: null },
    urls: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const guestName = ref(props.name ?? '');
const nameSaved = ref(Boolean(props.name));
const gallery = ref([]);
const next = ref(null);
const loading = ref(false);
const viewing = ref(null);
const photos = ref(props.album.photos);

const limits = props.album.limits;
const photoInput = ref(null);
const pickInput = ref(null);
const videoInput = ref(null);

const loadGallery = async (reset = false) => {
    if (loading.value) return;
    loading.value = true;

    try {
        const url = new URL(props.urls.gallery, window.location.origin);
        if (!reset && next.value) url.searchParams.set('before', next.value);
        const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const data = await response.json();

        gallery.value = reset ? data.items : [...gallery.value, ...data.items];
        next.value = data.next;
        photos.value = data.photos;
    } finally {
        loading.value = false;
    }
};

const { items, add, retry, clearDone } = useCameraUploads({
    reserveUrl: props.urls.reserve,
    limits,
    csrf: props.csrf,
    t,
    onDone: () => loadGallery(true),
});

const pick = (event) => {
    if (event.target.files?.length) add(event.target.files);
    event.target.value = '';
};

const saveName = async () => {
    if (!guestName.value.trim()) return;
    const data = await postJson(props.urls.name, { name: guestName.value });
    guestName.value = data.name;
    nameSaved.value = true;
};

/** Deleting asks once more on the same button: a second tap within a few seconds. */
const confirming = ref(false);
const remove = async (item) => {
    if (!item.delete_url) return;

    if (!confirming.value) {
        confirming.value = true;
        setTimeout(() => (confirming.value = false), 4000);
        return;
    }

    confirming.value = false;
    await postJson(item.delete_url, {}, 'DELETE');
    gallery.value = gallery.value.filter((media) => media.id !== item.id);
    viewing.value = null;
};

/** Reporting what should not be in the album: an optional reason, then a thank-you. */
const reporting = ref(false);
const reportReason = ref('');
const reportSent = ref(false);
const openViewer = (media) => {
    viewing.value = media;
    reporting.value = false;
    reportReason.value = '';
    reportSent.value = false;
};
const report = async () => {
    await postJson(viewing.value.report_url, { reason: reportReason.value });
    viewing.value.report_url = null;
    reporting.value = false;
    reportSent.value = true;
};

const accept = computed(() => (limits.allows_video ? 'image/*,video/*' : 'image/*'));
const remaining = computed(() => (limits.max_photos === null ? null : Math.max(0, limits.max_photos - photos.value)));
const busy = computed(() => items.value.some((item) => !['done', 'failed'].includes(item.state)));
const stateLabel = (item) => t(`camera.state_${item.state}`);

onMounted(() => props.entered && props.album.active && loadGallery(true));
</script>

<template>
    <div class="mx-auto flex w-full max-w-xl min-w-0 flex-col gap-6 px-4 py-8">
        <header class="flex flex-col items-center gap-2 text-center">
            <span class="text-3xl" aria-hidden="true">📸</span>
            <h1 class="font-display text-2xl font-semibold break-words">{{ album.title }}</h1>
            <p class="text-sm text-ink-muted">{{ album.date }}</p>
            <p v-if="album.welcome" class="mt-1 text-sm whitespace-pre-line">{{ album.welcome }}</p>
        </header>

        <p v-if="!album.active" class="rounded-2xl border border-line bg-surface-raised p-5 text-center text-sm text-ink-muted">{{ $t('camera.closed') }}</p>

        <!-- The couple's passcode, when they set one. -->
        <form v-else-if="!entered" :action="urls.enter" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <input type="hidden" name="_token" :value="csrf">
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('camera.passcode_label') }}</span>
                <input type="text" name="passcode" autocomplete="off" autocapitalize="off" required class="rounded-xl border border-line bg-surface px-4 py-3 text-center text-lg tracking-widest focus:border-brand-400 focus:outline-none">
            </label>
            <p v-if="errors.passcode" class="text-sm text-brand-700">{{ errors.passcode }}</p>
            <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('camera.enter') }}</button>
        </form>

        <template v-else>
            <!-- Who is sharing: asked once, shown to the couple. -->
            <form v-if="!nameSaved" class="flex gap-2" @submit.prevent="saveName">
                <input v-model="guestName" maxlength="40" :placeholder="$t('camera.name_placeholder')" class="min-w-0 flex-1 rounded-full border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <button type="submit" class="shrink-0 rounded-full border border-brand-600 px-4 py-2.5 text-sm font-semibold text-brand-700">{{ $t('camera.save_name') }}</button>
            </form>
            <p v-else class="text-center text-sm text-ink-muted">{{ $t('camera.sharing_as', { name: guestName }) }} · <button type="button" class="underline" @click="nameSaved = false">{{ $t('camera.change') }}</button></p>

            <section v-if="album.accepts_uploads" class="grid gap-3" :class="limits.allows_video ? 'grid-cols-3' : 'grid-cols-2'">
                <button type="button" class="flex flex-col items-center gap-2 rounded-2xl bg-brand-600 px-3 py-5 text-sm font-semibold text-white shadow-lg shadow-brand-900/20 transition active:scale-95" @click="photoInput.click()">
                    <span class="text-3xl" aria-hidden="true">📷</span>{{ $t('camera.take_photo') }}
                </button>
                <button type="button" class="flex flex-col items-center gap-2 rounded-2xl border border-line bg-surface-raised px-3 py-5 text-sm font-semibold transition active:scale-95" @click="pickInput.click()">
                    <span class="text-3xl" aria-hidden="true">🖼️</span>{{ $t('camera.pick_files') }}
                </button>
                <button v-if="limits.allows_video" type="button" class="flex flex-col items-center gap-2 rounded-2xl border border-line bg-surface-raised px-3 py-5 text-sm font-semibold transition active:scale-95" @click="videoInput.click()">
                    <span class="text-3xl" aria-hidden="true">🎥</span>{{ $t('camera.record_video') }}
                </button>

                <input ref="photoInput" type="file" accept="image/*" capture="environment" class="hidden" @change="pick">
                <input ref="pickInput" type="file" :accept="accept" multiple class="hidden" @change="pick">
                <input v-if="limits.allows_video" ref="videoInput" type="file" accept="video/*" capture="environment" class="hidden" @change="pick">
            </section>
            <p v-else class="rounded-2xl bg-surface-muted p-4 text-center text-sm text-ink-muted">{{ $t('camera.uploads_closed') }}</p>

            <p v-if="remaining !== null" class="-mt-3 text-center text-xs text-ink-muted">{{ $t('camera.remaining', { count: remaining }) }}</p>

            <!-- What is on its way. -->
            <section v-if="items.length" class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">{{ busy ? $t('camera.uploading') : $t('camera.uploaded') }}</h2>
                    <button v-if="!busy" type="button" class="text-xs text-ink-muted underline" @click="clearDone">{{ $t('camera.clear') }}</button>
                </div>
                <ul class="flex flex-col gap-2">
                    <li v-for="item in items" :key="item.id" class="flex min-w-0 items-center gap-3 rounded-xl border border-line bg-surface-raised p-2">
                        <img v-if="item.preview" :src="item.preview" alt="" class="size-12 shrink-0 rounded-lg object-cover">
                        <span v-else class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-surface-muted text-xl" aria-hidden="true">🎥</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs text-ink-muted">{{ item.error || stateLabel(item) }}</p>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-muted">
                                <div :class="['h-full rounded-full transition-all', item.state === 'failed' ? 'bg-brand-400' : item.state === 'done' ? 'bg-emerald-500' : 'bg-brand-600']" :style="{ width: `${Math.round((item.state === 'done' ? 1 : item.progress) * 100)}%` }"></div>
                            </div>
                        </div>
                        <button v-if="item.state === 'failed'" type="button" class="shrink-0 rounded-full border border-line px-3 py-1 text-xs font-medium" @click="retry(item)">{{ $t('camera.retry') }}</button>
                    </li>
                </ul>
            </section>

            <!-- The album. -->
            <section class="flex flex-col gap-3">
                <h2 class="text-sm font-semibold">{{ album.guests_can_view ? $t('camera.album') : $t('camera.my_photos') }}</h2>
                <p v-if="!gallery.length && !loading" class="rounded-2xl bg-surface-muted p-6 text-center text-sm text-ink-muted">{{ $t('camera.empty') }}</p>
                <ul class="grid grid-cols-3 gap-1.5">
                    <li v-for="media in gallery" :key="media.id" class="relative min-w-0">
                        <button type="button" class="block w-full" @click="openViewer(media)">
                            <img v-if="media.type === 'photo'" :src="media.thumb" alt="" loading="lazy" class="aspect-square w-full rounded-lg object-cover">
                            <span v-else class="flex aspect-square w-full items-center justify-center rounded-lg bg-ink/80 text-2xl text-white" aria-hidden="true">▶</span>
                        </button>
                    </li>
                </ul>
                <button v-if="next" type="button" class="self-center rounded-full border border-line px-5 py-2 text-sm font-medium" :disabled="loading" @click="loadGallery()">{{ $t('camera.more') }}</button>
            </section>
        </template>

        <!-- One photo or video, full screen. -->
        <div v-if="viewing" class="fixed inset-0 z-50 flex flex-col bg-black/95" @click.self="viewing = null">
            <div class="flex items-center justify-between gap-3 p-4 text-sm text-white">
                <span class="truncate">{{ viewing.by || '' }}</span>
                <div class="flex shrink-0 gap-2">
                    <button v-if="viewing.report_url && !reporting" type="button" class="rounded-full border border-white/40 px-3 py-1" @click="reporting = true">{{ $t('camera.report') }}</button>
                    <button v-if="viewing.delete_url" type="button" class="rounded-full border border-white/40 px-3 py-1" @click="remove(viewing)">{{ confirming ? $t('camera.confirm_delete') : $t('camera.delete') }}</button>
                    <button type="button" class="rounded-full border border-white/40 px-3 py-1" :aria-label="$t('camera.close')" @click="viewing = null">✕</button>
                </div>
            </div>
            <form v-if="reporting" class="flex gap-2 px-4 pb-3" @submit.prevent="report">
                <input v-model="reportReason" maxlength="300" :placeholder="$t('camera.report_reason')" class="min-w-0 flex-1 rounded-full border border-white/30 bg-white/10 px-4 py-2 text-sm text-white placeholder:text-white/60 focus:outline-none">
                <button type="submit" class="shrink-0 rounded-full bg-white px-4 py-2 text-sm font-semibold text-ink">{{ $t('camera.report_send') }}</button>
            </form>
            <p v-if="reportSent" class="px-4 pb-3 text-sm text-white/80">{{ $t('camera.reported') }}</p>
            <div class="flex min-h-0 flex-1 items-center justify-center p-2">
                <img v-if="viewing.type === 'photo'" :src="viewing.url" alt="" class="max-h-full max-w-full object-contain">
                <video v-else :src="viewing.url" controls playsinline class="max-h-full max-w-full"></video>
            </div>
        </div>
    </div>
</template>
