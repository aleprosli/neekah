<script setup>
/**
 * A labelled input. Errors come from the server, keyed by field name.
 *
 * A password field carries an eye button that shows what was typed, because on
 * a phone keyboard a mistyped password is the usual reason sign-in fails.
 */
import { computed, ref } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    modelValue: { type: [String, Number], default: '' },
    placeholder: { type: String, default: null },
    help: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    autocomplete: { type: String, default: null },
});

defineEmits(['update:modelValue']);

const isPassword = computed(() => props.type === 'password');
const revealed = ref(false);
const inputType = computed(() => (isPassword.value && revealed.value ? 'text' : props.type));
</script>

<template>
    <label class="flex flex-col gap-1.5">
        <span class="text-sm font-medium first-letter:uppercase">{{ label }}</span>
        <span class="relative flex">
            <input
                :type="inputType"
                :name="name"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :autocomplete="autocomplete"
                :class="[
                    'w-full rounded-xl border bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                    error ? 'border-brand-400' : 'border-line',
                    isPassword ? 'pr-11' : '',
                ]"
                v-bind="$attrs"
                @input="$emit('update:modelValue', $event.target.value)"
            >
            <button
                v-if="isPassword"
                type="button"
                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-ink-muted transition hover:text-ink focus-visible:text-brand-700 focus-visible:outline-none"
                :aria-label="revealed ? $t('copy.hide_password') : $t('copy.show_password')"
                :aria-pressed="revealed"
                @click="revealed = !revealed"
            >
                <svg v-if="revealed" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.7 5.1A10.8 10.8 0 0 1 12 5c7 0 10 7 10 7a13.2 13.2 0 0 1-1.7 2.7M6.6 6.6A13.5 13.5 0 0 0 2 12s3 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 1 0 4.2 4.2M2 2l20 20"/></svg>
                <svg v-else class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </span>
        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else-if="help" class="text-xs text-ink-muted">{{ help }}</span>
    </label>
</template>
