<script setup>
/** Every booking the couple has made, and what is still owed on each. */
import UiBadge from '../ui/UiBadge.vue';
import UiCategoryTile from '../ui/UiCategoryTile.vue';
import UiEmptyState from '../ui/UiEmptyState.vue';

defineProps({
    bookings: { type: Array, required: true },
    findVendorsUrl: { type: String, required: true },
    pagination: { type: String, default: '' },
});
</script>

<template>
    <UiEmptyState
        v-if="!bookings.length"
        icon="🗓️"
        title="Belum ada tempahan"
        message="Cari vendor, pilih pakej dan tempah terus dalam Neekah."
        action-label="Cari vendor"
        :action-url="findVendorsUrl"
    />

    <template v-else>
        <ul class="flex flex-col gap-4">
            <li v-for="booking in bookings" :key="booking.reference">
                <a :href="booking.url" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5 sm:flex-row sm:items-center">
                    <UiCategoryTile size="size-14" v-bind="booking.category" />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold">{{ booking.vendor }}</h2>
                            <UiBadge :label="booking.status_label" :tone="booking.status_tone" />
                        </div>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ booking.summary }}</p>
                    </div>

                    <div class="text-sm sm:text-right">
                        <p class="font-semibold">{{ booking.total }}</p>
                        <p class="text-xs text-ink-muted">Dibayar {{ booking.paid }}</p>
                    </div>
                </a>
            </li>
        </ul>

        <div v-if="pagination" class="mt-8" v-html="pagination"></div>
    </template>
</template>
