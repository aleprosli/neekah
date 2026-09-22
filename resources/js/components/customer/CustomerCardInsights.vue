<script setup>
/**
 * How the invitation is being read.
 *
 * The numbers are deliberately plain: opens, replies, heads confirmed, and how much
 * of the guest list has looked. A couple uses this to decide whether to send the
 * link again, so what matters is the gap between invited and replied, not a score.
 */
import { computed } from 'vue';
import UiLineChart from '../ui/UiLineChart.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    site: { type: Object, required: true },
    totals: { type: Object, required: true },
    daily: { type: Array, default: () => [] },
});

const series = computed(() =>
    props.daily.map((day) => ({ label: day.label, value: day.views, display: String(day.views) })),
);

const guestSeries = computed(() =>
    props.daily.map((day) => ({ label: day.label, value: day.guest_views, display: String(day.guest_views) })),
);

/** Of the people on the list, how many have opened their own link. */
const openedShare = computed(() => {
    if (!props.totals.invited) return null;

    return Math.round((props.totals.opened / props.totals.invited) * 100);
});
</script>

<template>
    <div class="flex flex-col gap-8">
        <div
            :class="[
                'flex flex-col gap-3 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between',
                site.published ? 'border-emerald-200 bg-emerald-50' : 'border-line bg-surface-raised',
            ]"
        >
            <div class="min-w-0">
                <p class="text-sm font-semibold">{{ site.published ? $t('card_insights.tersiar') : $t('card_insights.belum_tersiar') }}</p>
                <a v-if="site.published" :href="site.url" target="_blank" rel="noopener" class="mt-1 block truncate text-sm text-brand-700 underline underline-offset-4">{{ site.url }}</a>
                <p v-else class="mt-1 text-sm text-ink-muted">{{ $t('card_insights.kad_belum_disiarkan_jadi_nombor') }}</p>
            </div>
            <a :href="site.editUrl" class="shrink-0 rounded-full border border-line px-5 py-2.5 text-sm font-semibold transition hover:border-brand-400">{{ $t('card_insights.sunting_kad') }}</a>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <UiStatCard :label="$t('card_insights.kad_dibuka')" :value="totals.views" :hint="$t('card_insights.termasuk_yang_dikongsi_semula')" />
            <UiStatCard :label="$t('card_insights.dibuka_dari_pautan_peribadi')" :value="totals.guest_views" :hint="$t('card_insights.tetamu_yang_anda_hantar_pautan')" />
            <UiStatCard :label="$t('card_insights.jawapan_rsvp')" :value="totals.replies" :hint="$t('card_insights.hadir_tidak_hadir', { attending: totals.attending, declined: totals.declined })" :href="site.guestsUrl" />
            <UiStatCard :label="$t('card_insights.kepala_disahkan')" :value="totals.confirmed_pax" :hint="$t('card_insights.belum_menjawab_pax', { count: totals.awaiting_pax })" :href="site.guestsUrl" />
        </div>

        <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">{{ $t('card_insights.kad_dibuka_28_hari') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('card_insights.hari_yang_sunyi_juga_dikira') }}</p>
            </div>
            <UiLineChart :series="series" :empty="$t('card_insights.belum_ada_bacaan')" />
        </section>

        <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">{{ $t('card_insights.pautan_peribadi_28_hari') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('card_insights.ini_menunjukkan_senarai_tetamu_anda') }}</p>
            </div>
            <UiLineChart :series="guestSeries" :empty="$t('card_insights.belum_ada_bacaan')" />
        </section>

        <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">{{ $t('card_insights.senarai_tetamu') }}</h2>
            <p v-if="openedShare === null" class="text-sm text-ink-muted">{{ $t('card_insights.belum_ada_tetamu_dalam_senarai') }}</p>
            <template v-else>
                <p class="text-sm">{{ $t('card_insights.tetamu_membuka_pautan', { opened: totals.opened, invited: totals.invited, percent: openedShare }) }}</p>
                <div class="h-2 overflow-hidden rounded-full bg-surface-muted">
                    <div class="h-full rounded-full bg-brand-500" :style="{ width: `${openedShare}%` }" />
                </div>
                <a :href="site.guestsUrl" class="w-fit text-sm font-medium text-brand-600 underline underline-offset-4">{{ $t('card_insights.buka_senarai_tetamu') }}</a>
            </template>
        </section>

        <section class="rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">{{ $t('card_insights.ucapan_diluluskan') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ $t('card_insights.ucapan_dipapar_pada_kad', { count: totals.wishes }) }}</p>
        </section>
    </div>
</template>
