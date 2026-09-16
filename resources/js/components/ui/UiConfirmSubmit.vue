<script setup>
/**
 * A submit button that asks before it submits the form it sits in.
 *
 * UiConfirm posts a little form of its own, which is right for a bare action
 * like "delete this row". A form the visitor has filled in has to be sent as it
 * stands, so this hands the very same form back to the browser with
 * requestSubmit(), leaving any @submit handler — the upload progress bar — to
 * run exactly as it would have.
 */
import { ref } from 'vue';

defineProps({
    title: { type: String, required: true },
    message: { type: String, default: null },
    confirmLabel: { type: String, default: 'Teruskan' },
    cancelLabel: { type: String, default: 'Semak semula' },
    buttonClass: { type: String, default: 'w-full rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700' },
    disabled: { type: Boolean, default: false },
});

const trigger = ref(null);
const open = ref(false);

const ask = () => {
    // Let the browser point at whatever is still missing before we ask.
    if (trigger.value?.form?.reportValidity() === false) {
        return;
    }

    open.value = true;
};

const confirm = () => {
    open.value = false;
    trigger.value?.form?.requestSubmit();
};
</script>

<template>
    <button ref="trigger" type="submit" :class="buttonClass" :disabled="disabled" @click.prevent="ask"><slot /></button>

    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center sm:p-4" @click.self="open = false">
            <div class="w-full max-w-md rounded-t-3xl bg-surface-raised p-6 text-left shadow-2xl sm:rounded-3xl sm:p-7">
                <h2 class="font-display text-lg font-semibold">{{ title }}</h2>
                <p v-if="message" class="mt-1 text-sm text-ink-muted">{{ message }}</p>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:bg-surface-muted" @click="open = false">{{ cancelLabel }}</button>
                    <button type="button" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700" @click="confirm">{{ confirmLabel }}</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
