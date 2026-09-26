<script setup>
/**
 * Neekah Kenangan for a wedding guest, opened from the QR on their table.
 *
 * Three big buttons (take a photo, pick from the phone, and on Pro record a
 * video), then a screen that holds the guest until everything they picked
 * is up, so nobody closes the page halfway. Below, a wish for the couple:
 * written on every tier, spoken on Pro. A passcode screen first when the
 * couple set one; the guest's name is asked once and shown to the couple
 * next to what they shared.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { postJson, useCameraUploads } from '../../composables/useCameraUploads.js';
import { useVoiceRecorder } from '../../composables/useVoiceRecorder.js';
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

const { items, add, retry, clearDone, batch } = useCameraUploads({
    reserveUrl: props.urls.reserve,
    limits,
    csrf: props.csrf,
    t,
    onIdle: () => loadGallery(true),
});

/** The screen that holds the guest while a batch goes up. */
const holding = ref(false);

const pick = (event) => {
    if (event.target.files?.length) {
        clearDone();
        holding.value = true;
        add(event.target.files);
    }
    event.target.value = '';
};

const dismissHold = () => {
    holding.value = false;
    if (!batch.value.failed) clearDone();
};

// A tab closed mid-upload loses what had not arrived; ask first.
const warnBeforeLeaving = (event) => {
    if (!busy.value) return;
    event.preventDefault();
    event.returnValue = '';
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

/* A wish for the couple: written, or spoken on Pro. */
const wishMode = ref('text');
const wishText = ref('');
const wishSending = ref(false);
const wishSent = ref(false);
const wishError = ref('');
const voice = useVoiceRecorder({ maxSeconds: props.album.voice_max_seconds });

const sendWish = async () => {
    wishError.value = '';
    wishSending.value = true;

    try {
        if (wishMode.value === 'text') {
            if (!wishText.value.trim()) return;
            await postJson(props.urls.wish, { message: wishText.value });
            wishText.value = '';
        } else {
            if (!voice.blob.value) return;
            const form = new FormData();
            const extension = voice.blob.value.type.includes('mp4') ? 'm4a' : voice.blob.value.type.includes('ogg') ? 'ogg' : 'webm';
            form.append('audio', voice.blob.value, `ucapan.${extension}`);
            form.append('seconds', String(voice.seconds.value));
            const response = await fetch(props.urls.wish, {
                method: 'POST',
                body: form,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data?.errors ? Object.values(data.errors).flat()[0] : data?.message || t('camera.error_network'));
            voice.reset();
        }

        wishSent.value = true;
        setTimeout(() => (wishSent.value = false), 5000);
    } catch (error) {
        wishError.value = error.message;
    } finally {
        wishSending.value = false;
    }
};

const clock = (value) => `${Math.floor(value / 60)}:${String(value % 60).padStart(2, '0')}`;

const accept = computed(() => (limits.allows_video ? 'image/*,video/*' : 'image/*'));
const remaining = computed(() => (limits.max_photos === null ? null : Math.max(0, limits.max_photos - photos.value)));
const busy = computed(() => items.value.some((item) => !['done', 'failed'].includes(item.state)));
const stateLabel = (item) => t(`camera.state_${item.state}`);

onMounted(() => {
    if (props.entered && props.album.active) loadGallery(true);
    window.addEventListener('beforeunload', warnBeforeLeaving);
});
onBeforeUnmount(() => window.removeEventListener('beforeunload', warnBeforeLeaving));

// Once everything settles, say so for a moment and let the guest carry on.
watch(() => batch.value.settled, (settled) => {
    if (settled && holding.value && !batch.value.failed) setTimeout(dismissHold, 1600);
});
</script>

<template>
    <div class="mx-auto flex w-full max-w-xl min-w-0 flex-col gap-6 px-4 py-8">
        <header class="relative flex flex-col items-center gap-2 overflow-hidden rounded-3xl border border-gold-300/60 bg-linear-to-br from-brand-50 via-ivory to-ivory-deep px-5 py-8 text-center">
            <div class="pointer-events-none absolute -top-16 -right-16 size-48 rounded-full bg-brand-100/70 blur-3xl" aria-hidden="true"></div>
            <p class="relative text-[11px] font-semibold tracking-[0.3em] text-gold-600 uppercase">{{ $t('camera.brand') }}</p>
            <h1 class="relative font-display text-3xl font-semibold break-words">{{ album.title }}</h1>
            <p class="relative text-sm text-ink-muted">{{ album.date }}</p>
            <p v-if="album.welcome" class="relative mt-2 text-sm whitespace-pre-line">{{ album.welcome }}</p>
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

            <!-- A wish for the couple: written, or spoken on Pro. -->
            <section v-if="album.accepts_uploads" class="flex flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-5">
                <div>
                    <h2 class="font-display text-lg font-semibold">💌 {{ $t('camera.wish_title') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('camera.wish_help') }}</p>
                </div>

                <div v-if="album.allows_voice && voice.supported" class="grid grid-cols-2 gap-1 rounded-full bg-surface-muted p-1 text-sm font-semibold">
                    <button type="button" :class="['rounded-full py-2 transition', wishMode === 'text' ? 'bg-surface-raised shadow-sm' : 'text-ink-muted']" @click="wishMode = 'text'">✍️ {{ $t('camera.wish_write') }}</button>
                    <button type="button" :class="['rounded-full py-2 transition', wishMode === 'voice' ? 'bg-surface-raised shadow-sm' : 'text-ink-muted']" @click="wishMode = 'voice'">🎙️ {{ $t('camera.wish_record') }}</button>
                </div>

                <form v-if="wishMode === 'text'" class="flex flex-col gap-3" @submit.prevent="sendWish">
                    <textarea v-model="wishText" rows="3" :maxlength="album.wish_max_characters" :placeholder="$t('camera.wish_placeholder')" class="rounded-2xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none"></textarea>
                    <button type="submit" :disabled="wishSending || !wishText.trim()" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50">{{ wishSending ? '…' : $t('camera.wish_send') }}</button>
                </form>

                <div v-else class="flex flex-col items-center gap-3">
                    <p v-if="voice.state.value === 'denied'" class="rounded-xl bg-amber-50 px-4 py-3 text-center text-sm text-amber-900">{{ $t('camera.voice_denied') }}</p>
                    <template v-if="voice.state.value !== 'recorded'">
                        <button type="button" :class="['flex size-20 items-center justify-center rounded-full text-3xl text-white shadow-lg transition active:scale-95', voice.state.value === 'recording' ? 'animate-pulse bg-red-600' : 'bg-brand-600']" :aria-label="voice.state.value === 'recording' ? $t('camera.voice_stop') : $t('camera.voice_start')" @click="voice.state.value === 'recording' ? voice.stop() : voice.start()">{{ voice.state.value === 'recording' ? '■' : '🎙️' }}</button>
                        <p class="text-sm font-medium tabular-nums">{{ voice.state.value === 'recording' ? `${clock(voice.seconds.value)} / ${clock(album.voice_max_seconds)}` : $t('camera.voice_tap', { seconds: album.voice_max_seconds }) }}</p>
                    </template>
                    <template v-else>
                        <audio :src="voice.url.value" controls class="w-full"></audio>
                        <div class="grid w-full grid-cols-2 gap-2">
                            <button type="button" class="rounded-full border border-line py-3 text-sm font-semibold" @click="voice.reset()">{{ $t('camera.voice_redo') }}</button>
                            <button type="button" :disabled="wishSending" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white disabled:opacity-50" @click="sendWish">{{ wishSending ? '…' : $t('camera.wish_send') }}</button>
                        </div>
                    </template>
                </div>

                <p v-if="wishSent" class="rounded-xl bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-800">{{ $t('camera.wish_sent') }}</p>
                <p v-if="wishError" class="rounded-xl bg-brand-50 px-4 py-3 text-center text-sm text-brand-800">{{ wishError }}</p>
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

        <!-- Holds the guest until everything they picked is up. -->
        <div v-if="holding && items.length" class="fixed inset-0 z-50 flex items-end justify-center bg-ink/70 p-4 backdrop-blur-sm sm:items-center" role="dialog" aria-modal="true" aria-live="polite">
            <div class="flex w-full max-w-md flex-col gap-5 rounded-3xl bg-surface-raised p-6 shadow-2xl">
                <template v-if="!batch.settled">
                    <div class="flex items-center gap-4">
                        <span class="relative flex size-14 shrink-0 items-center justify-center">
                            <svg class="absolute inset-0 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" class="text-surface-muted" />
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-brand-600 transition-all duration-300" :stroke-dasharray="`${Math.round(batch.progress * 97.4)} 97.4`" />
                            </svg>
                            <span class="text-xs font-semibold tabular-nums">{{ Math.round(batch.progress * 100) }}%</span>
                        </span>
                        <div class="min-w-0">
                            <p class="font-display text-lg font-semibold">{{ $t('camera.hold_title') }}</p>
                            <p class="text-sm text-ink-muted">{{ $t('camera.hold_progress', { done: batch.done, total: batch.total }) }}</p>
                        </div>
                    </div>
                    <p class="rounded-xl bg-amber-50 px-4 py-2.5 text-xs font-medium text-amber-900">{{ $t('camera.hold_keep_open') }}</p>
                </template>
                <template v-else>
                    <div class="flex flex-col items-center gap-2 text-center">
                        <span :class="['flex size-14 items-center justify-center rounded-full text-2xl text-white', batch.failed ? 'bg-amber-500' : 'bg-emerald-500']">{{ batch.failed ? '!' : '✓' }}</span>
                        <p class="font-display text-lg font-semibold">{{ batch.failed ? $t('camera.hold_some_failed', { count: batch.failed }) : $t('camera.hold_done', { count: batch.done }) }}</p>
                    </div>
                </template>

                <ul class="flex max-h-48 gap-2 overflow-x-auto pb-1">
                    <li v-for="item in items" :key="item.id" class="relative size-14 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                        <img v-if="item.preview" :src="item.preview" alt="" class="size-full object-cover">
                        <span v-else class="flex size-full items-center justify-center text-xl" aria-hidden="true">🎥</span>
                        <span v-if="item.state === 'done'" class="absolute inset-0 flex items-center justify-center bg-emerald-600/60 text-white">✓</span>
                        <button v-else-if="item.state === 'failed'" type="button" class="absolute inset-0 flex items-center justify-center bg-brand-700/70 text-xs font-semibold text-white" @click="retry(item)">↻</button>
                        <span v-else class="absolute inset-x-0 bottom-0 h-1 bg-brand-600 transition-all" :style="{ width: `${Math.round(item.progress * 100)}%` }"></span>
                    </li>
                </ul>

                <button v-if="batch.settled" type="button" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white" @click="dismissHold">{{ $t('camera.hold_continue') }}</button>
            </div>
        </div>

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
                <img v-if="viewing.type === 'photo'" :src="viewing.display || viewing.url" alt="" class="max-h-full max-w-full object-contain">
                <video v-else :src="viewing.url" controls playsinline class="max-h-full max-w-full"></video>
            </div>
        </div>
    </div>
</template>
