<script setup>
/** The platform's numbers over a chosen window, all computed on the server. */
import UiBarChart from '../ui/UiBarChart.vue';
import UiDonutChart from '../ui/UiDonutChart.vue';
import UiLineChart from '../ui/UiLineChart.vue';
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    stats: { type: Array, required: true },
    charts: { type: Array, required: true },
    topVendors: { type: Array, required: true },
});

const charted = {
    bars: UiBarChart,
    line: UiLineChart,
    donut: UiDonutChart,
};
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <section v-for="chart in charts" :key="chart.title" class="min-w-0 rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">{{ chart.title }}</h2>
            <component :is="charted[chart.type]" class="mt-4" :series="chart.series" />
        </section>
    </div>

    <section class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
        <h2 class="font-semibold">Vendor terbaik mengikut skor</h2>
        <ul class="mt-4 flex flex-col gap-2">
            <li v-for="vendor in topVendors" :key="vendor.name" class="flex items-center justify-between gap-3 text-sm">
                <a :href="vendor.url" class="flex min-w-0 items-center gap-2 hover:text-brand-700">
                    <img v-if="vendor.illustration" :src="vendor.illustration" alt="" class="size-5 shrink-0 object-contain mix-blend-multiply">
                    <span v-else aria-hidden="true">{{ vendor.icon }}</span>
                    <span class="truncate">{{ vendor.name }}</span>
                </a>
                <span class="shrink-0 text-ink-muted">{{ vendor.score }}</span>
            </li>
        </ul>
    </section>
</template>
