<script setup>
/** The platform at a glance: the numbers, what needs approving, what just sold. */
import UiBadge from '../ui/UiBadge.vue';
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    stats: { type: Array, required: true },
    alert: { type: Object, default: null },
    pending: { type: Array, required: true },
    pendingUrl: { type: String, required: true },
    topVendors: { type: Array, required: true },
    recentBookings: { type: Array, required: true },
    bookingsUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <a v-if="alert" :href="alert.url" class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 transition hover:border-amber-400">
        <span class="text-lg">⚠️</span>
        <span><strong>{{ alert.count }}</strong> laporan vendor menunggu semakan anda.</span>
    </a>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Menunggu kelulusan</h2>
                <a :href="pendingUrl" class="text-sm font-medium text-brand-600 hover:underline">Semua</a>
            </div>

            <p v-if="!pending.length" class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">Tiada permohonan vendor baharu.</p>

            <ul v-else class="divide-y divide-line rounded-2xl border border-line">
                <li v-for="vendor in pending" :key="vendor.id" class="flex items-center gap-3 p-4">
                    <span :class="['flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br p-1 text-lg', vendor.tone]">
                        <img v-if="vendor.illustration" :src="vendor.illustration" alt="" class="size-full object-contain mix-blend-multiply">
                        <template v-else>{{ vendor.icon }}</template>
                    </span>

                    <div class="min-w-0 flex-1">
                        <a :href="vendor.url" class="block truncate font-medium hover:text-brand-700">{{ vendor.name }}</a>
                        <p class="truncate text-xs text-ink-muted">{{ vendor.summary }}</p>
                    </div>

                    <form :action="vendor.approve_url" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">Lulus</button>
                    </form>
                </li>
            </ul>
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Vendor teratas</h2>
            <ol class="divide-y divide-line rounded-2xl border border-line">
                <li v-for="(vendor, at) in topVendors" :key="vendor.id" class="flex items-center gap-3 p-4 text-sm">
                    <span class="w-5 shrink-0 text-center font-semibold text-ink-muted">{{ at + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <a :href="vendor.url" class="block truncate font-medium hover:text-brand-700">{{ vendor.name }}</a>
                        <p class="truncate text-xs text-ink-muted">{{ vendor.summary }}</p>
                    </div>
                    <span class="font-display font-semibold">{{ vendor.score }}</span>
                </li>
            </ol>
        </section>
    </div>

    <section class="mt-8 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold">Tempahan terkini</h2>
            <a :href="bookingsUrl" class="text-sm font-medium text-brand-600 hover:underline">Semua</a>
        </div>

        <div class="min-w-0 overflow-x-auto rounded-2xl border border-line">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Rujukan</th>
                        <th class="px-4 py-3 font-semibold">Vendor</th>
                        <th class="px-4 py-3 font-semibold">Pengantin</th>
                        <th class="px-4 py-3 text-right font-semibold">Jumlah</th>
                        <th class="px-4 py-3 text-right font-semibold">Komisen</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="booking in recentBookings" :key="booking.reference" class="transition hover:bg-surface-muted/60">
                        <td class="px-4 py-3"><a :href="booking.url" class="font-medium hover:text-brand-700">{{ booking.reference }}</a></td>
                        <td class="px-4 py-3">{{ booking.vendor }}</td>
                        <td class="px-4 py-3">{{ booking.customer }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ booking.total }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ booking.commission }}</td>
                        <td class="px-4 py-3"><UiBadge :label="booking.status_label" :tone="booking.status_tone" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
