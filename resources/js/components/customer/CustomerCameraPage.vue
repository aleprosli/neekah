<script setup>
/**
 * Kamera Majlis for the couple: the two tiers to buy before they have an
 * album, the album's link and usage once they do, and an upgrade to Pro.
 * Buying is an ordinary form post to the checkout, which sends them to pay.
 */
import { computed, ref } from 'vue';
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
                <p class="text-xs text-ink-muted">{{ $t('camera.qr_coming') }}</p>
            </div>
        </section>

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
