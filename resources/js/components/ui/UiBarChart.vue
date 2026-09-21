<script setup>
/**
 * Bars drawn as SVG from the brand tokens, with the same numbers available as
 * text underneath — a chart should never be the only way to read a figure.
 * Values arrive already formatted from the server.
 */
import { computed } from 'vue';

const props = defineProps({
    /** [{ label, value, display }] */
    series: { type: Array, default: () => [] },
    empty: { type: String, default: 'Tiada data untuk tempoh ini.' },
});

const max = computed(() => Math.max(0, ...props.series.map((row) => Number(row.value))));
const width = computed(() => Math.max(120, props.series.length * 44));
const hasData = computed(() => props.series.length > 0 && max.value > 0);

const heightOf = (value) => Math.max(1, Math.round((Number(value) / max.value) * 100));
</script>

<template>
    <p v-if="!hasData" class="py-8 text-center text-sm text-ink-muted">{{ empty }}</p>

    <template v-else>
        <div class="overflow-x-auto">
            <div class="min-w-full" :style="{ width: `${width}px` }">
                <svg :viewBox="`0 0 ${width} 120`" class="h-40 w-full" role="img" preserveAspectRatio="none">
                    <rect
                        v-for="(row, at) in series"
                        :key="row.label"
                        :x="at * 44 + 8"
                        :y="110 - heightOf(row.value)"
                        width="28"
                        :height="heightOf(row.value)"
                        rx="4"
                        fill="var(--color-brand-600)"
                    >
                        <title>{{ row.label }}: {{ row.display }}</title>
                    </rect>
                </svg>

                <ul class="mt-1 flex text-center text-[0.65rem] text-ink-muted">
                    <li v-for="row in series" :key="row.label" class="min-w-0 flex-1 truncate">{{ row.label }}</li>
                </ul>
            </div>
        </div>

        <details class="mt-2 text-xs text-ink-muted">
            <summary class="cursor-pointer">{{ $t('chart.lihat_nombor') }}</summary>
            <ul class="mt-2 flex flex-col gap-1">
                <li v-for="row in series" :key="row.label" class="flex justify-between gap-4"><span>{{ row.label }}</span><span>{{ row.display }}</span></li>
            </ul>
        </details>
    </template>
</template>
