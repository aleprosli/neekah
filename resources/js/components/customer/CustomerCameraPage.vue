<script setup>
/**
 * Neekah Kenangan for the couple: every album they bought (one per majlis:
 * akad nikah, sanding, bertandang), each opening its own page, and buying
 * another. With no album yet the page is the pitch and the tiers.
 *
 * Buying is an ordinary form post to the checkout, which sends them to pay;
 * the album's name and date travel with it and the payment creates it.
 */
import { computed, ref } from 'vue';
import { t } from '../../i18n.js';

const props = defineProps({
    wedding: { type: Object, required: true },
    albums: { type: Array, default: () => [] },
    buy: { type: Object, required: true },
    purchases: { type: Array, default: () => [] },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
    old: { type: Object, default: () => ({}) },
});

const hasAlbums = computed(() => props.albums.length > 0);
const title = ref(props.old.title ?? (hasAlbums.value ? '' : props.wedding.title));
const eventDate = ref(props.old.event_date ?? props.wedding.date);
const buying = ref(!hasAlbums.value || Object.keys(props.errors).length > 0);

const totals = computed(() => props.albums.reduce(
    (sum, album) => ({ photos: sum.photos + album.photos, videos: sum.videos + album.videos, wishes: sum.wishes + album.wishes }),
    { photos: 0, videos: 0, wishes: 0 },
));

const ringgit = (amount) => `RM${Number(amount).toLocaleString('en-MY', { maximumFractionDigits: 2 })}`;

const featuresOf = (tier) => {
    const limits = tier.limits;

    return [
        limits.max_photos ? t('camera.feature_photos_capped', { count: limits.max_photos }) : t('camera.feature_photos_unlimited'),
        t(limits.photo_pixels >= 3000 ? 'camera.feature_full_hd' : 'camera.feature_hd'),
        limits.allows_video
            ? t('camera.feature_video', { mb: limits.video_max_megabytes, minutes: Math.round(limits.video_max_seconds / 60) })
            : t('camera.feature_no_video'),
        t('camera.feature_messages'),
        tier.voice ? t('camera.feature_voice') : null,
        t('camera.feature_qr'),
        t('camera.feature_passcode'),
        t('camera.feature_retention', { days: props.buy.retention_days }),
    ].filter(Boolean);
};

const stateOf = (album) => {
    if (album.purged) return { label: t('camera.state_purged'), tone: 'bg-surface-muted text-ink-muted' };
    if (!album.active) return { label: t('camera.state_ended'), tone: 'bg-amber-50 text-amber-800' };

    return { label: t('camera.state_active'), tone: 'bg-emerald-50 text-emerald-800' };
};

const openBuy = () => {
    buying.value = true;
    requestAnimationFrame(() => document.getElementById('beli-kenangan')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
};
</script>

<template>
    <div class="flex min-w-0 flex-col gap-8">
        <!-- What it is, and the whole wedding at a glance. -->
        <section class="relative overflow-hidden rounded-3xl border border-gold-300/60 bg-linear-to-br from-brand-50 via-ivory to-ivory-deep p-6 sm:p-8 lg:p-10">
            <div class="pointer-events-none absolute -top-16 -right-16 size-64 rounded-full bg-brand-100/60 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-20 left-1/3 size-56 rounded-full bg-gold-300/30 blur-3xl" aria-hidden="true"></div>

            <div class="relative grid min-w-0 gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-[0.25em] text-gold-600 uppercase">{{ $t('camera.brand') }}</p>
                    <h2 class="mt-2 font-display text-3xl leading-tight font-semibold sm:text-4xl">{{ $t('camera.pitch_title') }}</h2>
                    <p class="mt-3 max-w-2xl text-sm text-ink-muted sm:text-base">{{ $t('camera.pitch_body') }}</p>

                    <ol class="mt-6 grid gap-3 sm:grid-cols-3">
                        <li v-for="(step, index) in ['step_scan', 'step_share', 'step_wish']" :key="step" class="flex min-w-0 items-center gap-3 rounded-2xl bg-surface-raised/80 p-3 ring-1 ring-line/70 backdrop-blur">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 font-display text-sm font-semibold text-white">{{ index + 1 }}</span>
                            <span class="min-w-0 text-sm font-medium">{{ $t(`camera.${step}`) }}</span>
                        </li>
                    </ol>
                </div>

                <div v-if="hasAlbums" class="grid grid-cols-3 gap-3 lg:w-80">
                    <div v-for="stat in [['photos', totals.photos], ['videos', totals.videos], ['wishes', totals.wishes]]" :key="stat[0]" class="rounded-2xl bg-surface-raised/90 p-3 text-center ring-1 ring-line/70">
                        <p class="font-display text-2xl font-semibold">{{ stat[1] }}</p>
                        <p class="text-xs text-ink-muted">{{ $t(`camera.${stat[0]}`) }}</p>
                    </div>
                    <button type="button" class="col-span-3 rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/15 transition hover:bg-brand-700" @click="openBuy">＋ {{ $t('camera.add_album') }}</button>
                </div>
            </div>
        </section>

        <!-- Every album, one per majlis. -->
        <section v-if="hasAlbums" class="flex min-w-0 flex-col gap-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ $t('camera.your_albums') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('camera.your_albums_help') }}</p>
                </div>
            </div>

            <ul class="grid min-w-0 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <li v-for="album in albums" :key="album.id" class="min-w-0">
                    <a :href="album.show_url" class="group flex h-full min-w-0 flex-col overflow-hidden rounded-3xl border border-line bg-surface-raised shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg">
                        <!-- The newest photos, or a placeholder until guests share. -->
                        <div class="relative aspect-[16/10] overflow-hidden bg-linear-to-br from-brand-100 via-brand-50 to-ivory-deep">
                            <div v-if="album.covers.length" :class="['grid size-full gap-0.5', album.covers.length > 1 ? 'grid-cols-2' : 'grid-cols-1']">
                                <img v-for="(cover, index) in album.covers.slice(0, 4)" :key="cover" :src="cover" alt="" loading="lazy" decoding="async" :class="['size-full object-cover transition duration-500 group-hover:scale-105', album.covers.length === 3 && index === 0 ? 'row-span-2' : '']">
                            </div>
                            <div v-else class="flex size-full flex-col items-center justify-center gap-2 text-brand-700/70">
                                <span class="text-4xl" aria-hidden="true">📸</span>
                                <span class="text-xs font-medium">{{ $t('camera.no_photos_yet') }}</span>
                            </div>
                            <span :class="['absolute top-3 left-3 rounded-full px-3 py-1 text-xs font-semibold shadow-sm', stateOf(album).tone]">{{ stateOf(album).label }}</span>
                            <span :class="['absolute top-3 right-3 rounded-full px-3 py-1 text-xs font-semibold shadow-sm', album.tier === 'pro' ? 'bg-ink text-white' : 'bg-surface-raised text-ink']">{{ album.tier_label }}</span>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-3 p-5">
                            <div class="min-w-0">
                                <h3 class="truncate font-display text-lg font-semibold">{{ album.title }}</h3>
                                <p class="text-sm text-ink-muted">{{ album.date }}</p>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                <span>📷 {{ album.photos }}<span v-if="album.max_photos" class="text-ink-muted">/{{ album.max_photos }}</span></span>
                                <span>🎥 {{ album.videos }}</span>
                                <span>💬 {{ album.wishes }}</span>
                            </div>
                            <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-3 text-xs text-ink-muted">
                                <span v-if="album.active" class="truncate">{{ $t('camera.kept_until', { date: album.expires }) }}</span>
                                <span v-else class="truncate">{{ $t('camera.ended_on', { date: album.expires }) }}</span>
                                <span class="shrink-0 font-semibold text-brand-700 group-hover:underline">{{ $t('camera.open') }} →</span>
                            </div>
                        </div>
                    </a>
                </li>

                <li class="min-w-0">
                    <button type="button" class="flex h-full min-h-56 w-full flex-col items-center justify-center gap-3 rounded-3xl border-2 border-dashed border-brand-200 bg-brand-50/40 p-6 text-center transition hover:border-brand-400 hover:bg-brand-50" @click="openBuy">
                        <span class="flex size-12 items-center justify-center rounded-full bg-brand-600 text-2xl text-white">＋</span>
                        <span class="font-display text-lg font-semibold">{{ $t('camera.add_album') }}</span>
                        <span class="max-w-xs text-sm text-ink-muted">{{ $t('camera.add_album_help') }}</span>
                    </button>
                </li>
            </ul>
        </section>

        <!-- A new album: its name and date, then a tier. -->
        <section v-if="buying" id="beli-kenangan" class="flex min-w-0 scroll-mt-24 flex-col gap-5">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ hasAlbums ? $t('camera.buy_another_title') : $t('camera.buy_title') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('camera.buy_help') }}</p>
            </div>

            <p v-if="errors.tier" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ errors.tier }}</p>

            <form v-if="buy.can_checkout" :action="buy.checkout_url" method="POST" class="flex min-w-0 flex-col gap-5">
                <input type="hidden" name="_token" :value="csrf">

                <div class="grid min-w-0 gap-4 rounded-3xl border border-line bg-surface-raised p-5 sm:grid-cols-2 sm:p-6">
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('camera.album_name') }}</span>
                        <input v-model="title" name="title" maxlength="120" :placeholder="$t('camera.album_name_placeholder')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <span v-if="errors.title" class="text-xs text-brand-700">{{ errors.title }}</span>
                        <span v-else class="text-xs text-ink-muted">{{ $t('camera.album_name_help') }}</span>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('camera.album_date') }}</span>
                        <input v-model="eventDate" name="event_date" type="date" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <span v-if="errors.event_date" class="text-xs text-brand-700">{{ errors.event_date }}</span>
                        <span v-else class="text-xs text-ink-muted">{{ $t('camera.album_date_help', { days: buy.retention_days }) }}</span>
                    </label>
                </div>

                <div class="grid min-w-0 gap-5 md:grid-cols-2">
                    <article
                        v-for="tier in buy.tiers"
                        :key="tier.value"
                        :class="[
                            'relative flex min-w-0 flex-col gap-5 overflow-hidden rounded-3xl border p-6 sm:p-7',
                            tier.value === 'pro' ? 'border-brand-300 bg-linear-to-b from-brand-50 to-surface-raised shadow-lg shadow-brand-900/5' : 'border-line bg-surface-raised',
                        ]"
                    >
                        <span v-if="tier.value === 'pro'" class="absolute top-5 right-5 rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">{{ $t('camera.most_loved') }}</span>
                        <div>
                            <h3 class="font-display text-xl font-semibold">{{ tier.label }}</h3>
                            <p class="mt-2 font-display text-4xl font-semibold">{{ ringgit(tier.price) }}</p>
                            <p class="text-xs text-ink-muted">{{ $t('camera.one_off') }}</p>
                        </div>
                        <ul class="flex flex-1 flex-col gap-2.5 text-sm">
                            <li v-for="line in featuresOf(tier)" :key="line" class="flex gap-2.5"><span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-brand-600 text-[10px] text-white" aria-hidden="true">✓</span><span>{{ line }}</span></li>
                        </ul>
                        <button type="submit" name="tier" :value="tier.value" :class="['w-full rounded-full py-3 text-sm font-semibold transition', tier.value === 'pro' ? 'bg-brand-600 text-white hover:bg-brand-700' : 'border border-brand-600 text-brand-700 hover:bg-brand-50']">
                            {{ $t('camera.buy_for', { amount: ringgit(tier.price) }) }}
                        </button>
                    </article>
                </div>
            </form>
            <p v-else class="rounded-2xl bg-surface-muted p-5 text-center text-sm text-ink-muted">{{ $t('camera.checkout_soon') }}</p>

            <p class="text-xs text-ink-muted">{{ $t('camera.payment_note', { days: buy.retention_days }) }}</p>
        </section>

        <section v-if="purchases.length" class="min-w-0 overflow-hidden rounded-3xl border border-line bg-surface-raised">
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">{{ $t('camera.receipts') }}</h2>
            <ul class="divide-y divide-line text-sm">
                <li v-for="purchase in purchases" :key="purchase.reference" class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3">
                    <span class="font-mono text-xs">{{ purchase.reference }}</span>
                    <span class="min-w-0 truncate font-medium">{{ purchase.album }}</span>
                    <span class="text-ink-muted">{{ purchase.tier }}<template v-if="purchase.kind === 'upgrade'"> · {{ $t('camera.upgrade') }}</template></span>
                    <span class="ml-auto font-medium">{{ purchase.amount }}</span>
                    <span class="text-ink-muted">{{ purchase.paid_at }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
