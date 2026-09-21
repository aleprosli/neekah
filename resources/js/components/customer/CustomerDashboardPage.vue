<script setup>
/**
 * The couple's command centre: what the wedding costs so far, who is booked,
 * and which categories still have nobody.
 */
import UiBadge from '../ui/UiBadge.vue';
import UiCategoryTile from '../ui/UiCategoryTile.vue';
import UiEmptyState from '../ui/UiEmptyState.vue';
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    hasWedding: { type: Boolean, required: true },
    createUrl: { type: String, required: true },
    findVendorsUrl: { type: String, required: true },
    stats: { type: Array, default: () => [] },
    budget: { type: Object, default: null },
    bookings: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});
</script>

<template>
    <UiEmptyState
        v-if="!hasWedding"
        icon="💍"
        :title="$t('customer.start_project_title')"
        :message="$t('customer.tetapkan_tarikh_lokasi_dan_bajet')"
        :action-label="$t('customer.start_project_action')"
        :action-url="createUrl"
    />

    <template v-else>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
        </div>

        <div class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
            <div class="flex flex-wrap items-center justify-between gap-x-3 text-sm">
                <span class="font-medium">{{ $t('customer.budget_usage') }}</span>
                <span class="text-ink-muted">{{ budget.caption }}</span>
            </div>

            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-muted">
                <div :class="['h-full rounded-full transition-all', budget.over ? 'bg-amber-500' : 'bg-brand-600']" :style="{ width: `${budget.percent}%` }"></div>
            </div>

            <p v-if="budget.over" class="mt-2 text-xs text-amber-700">Anda telah melebihi bajet sebanyak {{ budget.overBy }}.</p>
        </div>

        <slot name="couple" />

        <div class="mt-8 grid gap-8 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
            <section class="flex min-w-0 flex-col gap-4">
                <h2 class="font-display text-xl font-semibold">{{ $t('customer.your_vendors') }}</h2>

                <p v-if="!bookings.length" class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">{{ $t('customer.belum_ada_vendor_ditempah') }}<a :href="findVendorsUrl" class="font-medium text-brand-600 underline underline-offset-4">{{ $t('common.find_vendors') }}</a> untuk mula.
                </p>

                <ul v-else class="divide-y divide-line rounded-2xl border border-line">
                    <li v-for="booking in bookings" :key="booking.reference">
                        <a :href="booking.url" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                            <UiCategoryTile v-bind="booking.category" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ booking.vendor }}</p>
                                <p class="truncate text-sm text-ink-muted">{{ booking.summary }}</p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold">{{ booking.total }}</p>
                                <UiBadge :label="booking.status_label" :tone="booking.status_tone" />
                            </div>
                        </a>
                    </li>
                </ul>
            </section>

            <section class="flex min-w-0 flex-col gap-4">
                <h2 class="font-display text-xl font-semibold">{{ $t('customer.checklist_categories') }}</h2>
                <ul class="flex flex-col gap-1 rounded-2xl border border-line p-3">
                    <li v-for="category in categories" :key="category.name">
                        <a :href="category.url" class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-sm transition hover:bg-surface-muted">
                            <span :class="['flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold', category.booked ? 'bg-emerald-500 text-white' : 'border border-line']">
                                {{ category.booked ? '✓' : '' }}
                            </span>
                            <img v-if="category.illustration" :src="category.illustration" alt="" class="size-5 shrink-0 object-contain mix-blend-multiply">
                            <span v-else class="size-5 shrink-0 text-center" aria-hidden="true">{{ category.icon }}</span>
                            <span :class="category.booked ? 'text-ink-muted line-through' : ''">{{ category.name }}</span>
                        </a>
                    </li>
                </ul>
            </section>
        </div>
    </template>
</template>
