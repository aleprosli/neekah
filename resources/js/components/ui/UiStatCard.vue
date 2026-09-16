<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: null },
    href: { type: String, default: null },
    trend: { type: Number, default: null },
});

const trendClass = computed(() => {
    if (props.trend === null) return '';
    if (props.trend > 0) return 'text-emerald-700';
    return props.trend < 0 ? 'text-amber-700' : 'text-ink-muted';
});
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href"
        :class="['flex flex-col gap-1 rounded-2xl border border-line bg-surface-raised p-5', href ? 'transition hover:border-brand-300' : '']"
    >
        <span class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ label }}</span>
        <span class="flex flex-wrap items-baseline gap-2">
            <span class="font-display text-2xl font-semibold">{{ value }}</span>
            <span v-if="trend !== null" :class="['text-xs font-semibold', trendClass]">{{ trend > 0 ? '+' : '' }}{{ trend.toFixed(1) }}%</span>
        </span>
        <span v-if="hint" class="text-xs text-ink-muted">{{ hint }}</span>
    </component>
</template>
