<script setup>
/**
 * How this vendor's points, score and tier are earned, and how the last months
 * actually went. Everything here is already computed on the server; the page
 * only chooses how to show it.
 */
import UiBarChart from '../ui/UiBarChart.vue';
import UiLineChart from '../ui/UiLineChart.vue';
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    stats: { type: Array, required: true },
    period: { type: Object, required: true },
    periodStats: { type: Array, required: true },
    charts: { type: Object, required: true },
    tiers: { type: Array, required: true },
    progress: { type: Object, required: true },
    earnable: { type: Array, required: true },
    history: { type: Array, required: true },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-xl font-semibold">Prestasi {{ period.label }} terakhir</h2>
            <div class="flex flex-wrap gap-2">
                <a
                    v-for="choice in period.choices"
                    :key="choice.months"
                    :href="choice.url"
                    :class="[
                        'rounded-full border px-4 py-1.5 text-xs font-medium transition',
                        choice.months === period.months ? 'border-brand-600 bg-brand-600 text-white' : 'border-line hover:border-brand-400',
                    ]"
                >{{ choice.label }}</a>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <UiStatCard v-for="stat in periodStats" :key="stat.label" v-bind="stat" />
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <h3 class="text-sm font-semibold">Pendapatan mengikut bulan</h3>
                <UiBarChart class="mt-4" :series="charts.revenue" />
            </div>
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <h3 class="text-sm font-semibold">Majlis selesai</h3>
                <UiBarChart class="mt-4" :series="charts.completed" />
            </div>
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <h3 class="text-sm font-semibold">Rating dari masa ke masa</h3>
                <UiLineChart class="mt-4" :series="charts.rating" empty="Belum cukup review untuk menunjukkan aliran." />
            </div>
        </div>
    </section>

    <section class="mt-8 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">Tahap anda</h2>

        <ol class="flex flex-col gap-2 sm:flex-row sm:gap-3">
            <li
                v-for="tier in tiers"
                :key="tier.label"
                :class="[
                    'flex flex-1 items-center gap-3 rounded-2xl border p-4 sm:flex-col sm:items-start sm:gap-1',
                    tier.is_current ? 'border-brand-300 bg-brand-50/60' : 'border-line bg-surface-raised',
                ]"
            >
                <span :class="['flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold', tier.reached ? 'bg-brand-600 text-white' : 'border border-line text-ink-muted']">
                    {{ tier.reached ? '✓' : tier.step }}
                </span>
                <span :class="['text-sm font-semibold', tier.is_current ? 'text-brand-700' : '']">{{ tier.label }}</span>
            </li>
        </ol>

        <p v-if="progress.locked" class="rounded-2xl border border-line bg-surface-muted p-4 text-sm text-ink-muted">
            Tahap anda telah dikunci oleh admin. Hubungi kami jika anda rasa ia perlu disemak semula.
        </p>

        <div v-else-if="progress.next" class="rounded-2xl border border-line bg-surface-raised p-5">
            <h3 class="text-sm font-semibold">Untuk naik ke {{ progress.next }} Vendor</h3>
            <ul class="mt-3 flex flex-col gap-2 text-sm">
                <li v-for="requirement in progress.requirements" :key="requirement.label" class="flex items-center gap-3">
                    <span :class="['flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold', requirement.met ? 'bg-emerald-500 text-white' : 'border border-line text-ink-muted']">
                        {{ requirement.met ? '✓' : '' }}
                    </span>
                    <span :class="requirement.met ? 'text-ink-muted' : ''">{{ requirement.label }}</span>
                    <span class="ml-auto shrink-0 font-medium">{{ requirement.current }} <span class="text-ink-muted">/ {{ requirement.target }}</span></span>
                </li>
            </ul>
            <p v-if="progress.clean_record_note" class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">{{ progress.clean_record_note }}</p>
        </div>

        <p v-else class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            🏆 Anda berada di tahap tertinggi. Kekalkan rating, response rate dan rekod bersih untuk terus disyorkan.
        </p>
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Cara point diberi</h2>
            <div class="overflow-x-auto rounded-2xl border border-line">
                <table class="w-full text-sm">
                    <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Aktiviti</th>
                            <th class="px-4 py-3 text-right font-semibold">Point</th>
                            <th class="px-4 py-3 text-right font-semibold">Anda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="reason in earnable" :key="reason.label">
                            <td class="px-4 py-3">{{ reason.label }}</td>
                            <td class="px-4 py-3 text-right text-ink-muted">+{{ reason.points }}</td>
                            <td class="px-4 py-3 text-right font-medium">
                                <template v-if="reason.earned !== null">
                                    +{{ reason.earned }}
                                    <span v-if="reason.awards" class="text-xs font-normal text-ink-muted">({{ reason.awards }}×)</span>
                                </template>
                                <span v-else class="text-ink-muted">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-ink-muted">Point tidak diberikan hanya kerana menerima enquiry. Booking sebenar, pembayaran dan perkhidmatan yang selesai menjadi faktor utama.</p>
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Sejarah point</h2>
            <p v-if="!history.length" class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">
                Belum ada point. Lengkapkan profil dan pakej untuk mula mengumpul.
            </p>
            <ul v-else class="divide-y divide-line rounded-2xl border border-line">
                <li v-for="entry in history" :key="entry.id" class="flex items-center gap-3 p-4 text-sm">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ entry.reason }}</p>
                        <p class="text-xs text-ink-muted">{{ entry.awarded_at }}</p>
                    </div>
                    <span :class="['font-display font-semibold', entry.points > 0 ? 'text-emerald-600' : 'text-red-600']">{{ entry.points > 0 ? '+' : '' }}{{ entry.points }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
