<script setup>
/** Nothing here yet, and the one thing to do about it. */
defineProps({
    icon: { type: String, default: null },
    title: { type: String, required: true },
    message: { type: String, default: null },
    actionLabel: { type: String, default: null },
    actionUrl: { type: String, default: null },
});
</script>

<template>
    <div class="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
        <span v-if="icon" class="text-4xl" aria-hidden="true">{{ icon }}</span>
        <h2 class="text-lg font-semibold">{{ title }}</h2>
        <p v-if="message" class="max-w-sm text-sm text-ink-muted">{{ message }}</p>
        <a v-if="actionUrl" :href="actionUrl" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ actionLabel }}</a>
    </div>
</template>
