<script setup>
/** A trend line. Two points are the minimum that can show a direction. */
import { computed } from 'vue';

const props = defineProps({
    series: { type: Array, default: () => [] },
    empty: { type: String, default: 'Tiada data untuk tempoh ini.' },
});

const max = computed(() => Math.max(0, ...props.series.map((row) => Number(row.value))));
const hasData = computed(() => props.series.length >= 2 && max.value > 0);

const points = computed(() => {
    const step = props.series.length > 1 ? 300 / (props.series.length - 1) : 0;

    return props.series
        .map((row, at) => `${(at * step).toFixed(1)},${(100 - (Number(row.value) / Math.max(max.value, 0.001)) * 90).toFixed(1)}`)
        .join(' ');
});
</script>

<template>
    <p v-if="!hasData" class="py-8 text-center text-sm text-ink-muted">{{ empty }}</p>

    <template v-else>
        <svg viewBox="0 0 300 110" class="h-32 w-full" role="img" preserveAspectRatio="none">
            <polyline :points="points" fill="none" stroke="var(--color-brand-600)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
        </svg>

        <div class="flex justify-between text-[0.65rem] text-ink-muted">
            <span>{{ series[0].label }}</span>
            <span>{{ series[series.length - 1].label }}</span>
        </div>

        <details class="mt-2 text-xs text-ink-muted">
            <summary class="cursor-pointer">{{ $t('chart.lihat_nombor') }}</summary>
            <ul class="mt-2 flex flex-col gap-1">
                <li v-for="row in series" :key="row.label" class="flex justify-between gap-4"><span>{{ row.label }}</span><span>{{ row.display }}</span></li>
            </ul>
        </details>
    </template>
</template>
