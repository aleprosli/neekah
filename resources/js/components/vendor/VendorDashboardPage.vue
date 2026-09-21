<script setup>
/** The vendor's opening screen: the numbers, then what is coming up. */
import UiBadge from '../ui/UiBadge.vue';
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    stats: { type: Array, required: true },
    upcoming: { type: Array, required: true },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <section class="mt-8 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">{{ $t('vendor_dashboard.tempahan_terdekat') }}</h2>

        <p v-if="!upcoming.length" class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">{{ $t('vendor_dashboard.tiada_tempahan_akan_datang_booking') }}</p>

        <ul v-else class="divide-y divide-line rounded-2xl border border-line">
            <li v-for="booking in upcoming" :key="booking.reference">
                <a :href="booking.url" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                    <div class="w-14 shrink-0 text-center">
                        <p class="font-display text-xl leading-none font-semibold">{{ booking.day }}</p>
                        <p class="text-[11px] text-ink-muted uppercase">{{ booking.month }}</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ booking.customer }}</p>
                        <p class="truncate text-sm text-ink-muted">{{ booking.package_name }} · {{ booking.reference }}</p>
                    </div>
                    <UiBadge :label="booking.status_label" :tone="booking.status_tone" />
                </a>
            </li>
        </ul>
    </section>
</template>
