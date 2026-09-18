<script setup>
/**
 * A destructive action behind a dialog. The form posts normally, so the action
 * still works exactly as the server expects it to.
 */
import { ref } from 'vue';

defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    title: { type: String, required: true },
    message: { type: String, default: null },
    confirmLabel: { type: String, default: 'Teruskan' },
    cancelLabel: { type: String, default: 'Batal' },
    tone: { type: String, default: 'brand' },
    // Mounted straight from Blade there is no slot to fill, so the trigger's
    // wording can come through as a prop instead.
    label: { type: String, default: null },
    triggerClass: { type: String, default: 'rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400 hover:text-brand-700' },
    /** Extra fields the confirmed form posts, e.g. { status: 'approved' }. */
    fields: { type: Object, default: () => ({}) },
    csrf: { type: String, required: true },
});

const open = ref(false);
</script>

<template>
    <button type="button" :class="triggerClass" @click="open = true"><slot>{{ label }}</slot></button>

    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @click.self="open = false">
            <div class="w-full max-w-md rounded-t-3xl bg-surface-raised p-6 text-left shadow-2xl sm:rounded-3xl sm:p-7">
                <div class="flex items-start gap-4">
                    <span :class="['flex size-11 shrink-0 items-center justify-center rounded-2xl', tone === 'danger' ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-600']">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path v-if="tone === 'danger'" d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6" />
                            <template v-else><path d="M12 9v4M12 17h.01" /><circle cx="12" cy="12" r="9" /></template>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-display text-lg font-semibold">{{ title }}</h2>
                        <p v-if="message" class="mt-1 text-sm text-ink-muted">{{ message }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:bg-surface-muted" @click="open = false">{{ cancelLabel }}</button>
                    <form :action="action" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <input v-if="['PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())" type="hidden" name="_method" :value="method">
                        <input v-for="(value, field) in fields" :key="field" type="hidden" :name="field" :value="value">
                        <button
                            type="submit"
                            :class="['w-full rounded-full px-5 py-2.5 text-sm font-semibold transition sm:w-auto', tone === 'danger' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-brand-600 text-white hover:bg-brand-700']"
                        >{{ confirmLabel }}</button>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>
