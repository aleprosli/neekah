<script setup>
/** The bar an upload fills, plus whatever went wrong if it did. */
defineProps({
    uploading: { type: Boolean, default: false },
    percent: { type: Number, default: 0 },
    error: { type: String, default: '' },
    label: { type: String, default: 'Memuat naik gambar' },
});
</script>

<template>
    <div v-if="uploading" class="flex flex-col gap-1.5" role="status" aria-live="polite">
        <div class="flex items-center justify-between text-xs text-ink-muted">
            <span>{{ label }}…</span>
            <span>{{ percent }}%</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-surface-muted">
            <div class="h-full rounded-full bg-brand-600 transition-all duration-200" :style="{ width: `${percent}%` }"></div>
        </div>
        <p v-if="percent >= 100" class="text-xs text-ink-muted">Memproses gambar di server…</p>
    </div>

    <p v-else-if="error" class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ error }}</p>
</template>
