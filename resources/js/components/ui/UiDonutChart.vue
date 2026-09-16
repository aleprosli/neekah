<script setup>
/** A share-of-total ring, with the same numbers listed beside it. */
import { computed } from 'vue';

const props = defineProps({
    /** [{ label, value, display }] */
    series: { type: Array, default: () => [] },
    empty: { type: String, default: 'Tiada data untuk tempoh ini.' },
});

const SHADES = [
    'var(--color-brand-600)',
    'var(--color-brand-400)',
    'var(--color-gold-500)',
    'var(--color-brand-800)',
    'var(--color-brand-200)',
    'var(--color-gold-300)',
];

const rows = computed(() => props.series.filter((row) => Number(row.value) > 0));
const total = computed(() => rows.value.reduce((sum, row) => sum + Number(row.value), 0));

/** Each slice as a dash pattern, offset by everything drawn before it. */
const slices = computed(() => {
    let offset = 0;

    return rows.value.map((row, at) => {
        const share = Math.round((Number(row.value) / total.value) * 10000) / 100;
        const slice = {
            ...row,
            share,
            colour: SHADES[at % SHADES.length],
            dash: `${share} ${100 - share}`,
            dashOffset: 100 - offset,
        };

        offset += share;

        return slice;
    });
});
</script>

<template>
    <p v-if="!rows.length || total <= 0" class="py-8 text-center text-sm text-ink-muted">{{ empty }}</p>

    <div v-else class="flex flex-wrap items-center gap-6">
        <svg viewBox="0 0 42 42" class="size-32 shrink-0 -rotate-90" role="img">
            <circle
                v-for="slice in slices"
                :key="slice.label"
                cx="21"
                cy="21"
                r="15.915"
                fill="none"
                :stroke="slice.colour"
                stroke-width="6"
                :stroke-dasharray="slice.dash"
                :stroke-dashoffset="slice.dashOffset"
            >
                <title>{{ slice.label }}: {{ slice.display }}</title>
            </circle>
        </svg>

        <ul class="flex min-w-0 flex-1 flex-col gap-1.5 text-sm">
            <li v-for="slice in slices" :key="`legend-${slice.label}`" class="flex items-center justify-between gap-3">
                <span class="flex min-w-0 items-center gap-2">
                    <span class="size-2.5 shrink-0 rounded-full" :style="{ background: slice.colour }"></span>
                    <span class="truncate">{{ slice.label }}</span>
                </span>
                <span class="shrink-0 text-ink-muted">{{ slice.display }}</span>
            </li>
        </ul>
    </div>
</template>
