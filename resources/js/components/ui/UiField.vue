<script setup>
/** A labelled input. Errors come from the server, keyed by field name. */
defineProps({
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
</script>

<template>
    <label class="flex flex-col gap-1.5">
        <span class="text-sm font-medium">{{ label }}</span>
        <input
            :type="type"
            :name="name"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :autocomplete="autocomplete"
            :class="[
                'rounded-xl border bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                error ? 'border-brand-400' : 'border-line',
            ]"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else-if="help" class="text-xs text-ink-muted">{{ help }}</span>
    </label>
</template>
