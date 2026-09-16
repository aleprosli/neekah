<script setup>
/** Status pills. The tone is decided on the server and passed in. */
defineProps({
    label: { type: String, required: true },
    tone: { type: String, default: 'muted' },
});

const tones = {
    amber: 'bg-amber-100 text-amber-800',
    emerald: 'bg-emerald-100 text-emerald-800',
    sky: 'bg-sky-100 text-sky-800',
    brand: 'bg-brand-50 text-brand-700',
    red: 'bg-red-100 text-red-800',
    muted: 'bg-surface-muted text-ink-muted',
};
</script>

<template>
    <span :class="['inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap', tones[tone] || tones.muted]">{{ label }}</span>
</template>
