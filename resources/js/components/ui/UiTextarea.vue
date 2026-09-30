<script setup>
defineProps({
    label: { type: String, required: true },
    name: { type: String, required: true },
    modelValue: { type: String, default: '' },
    rows: { type: [String, Number], default: 4 },
    placeholder: { type: String, default: null },
    help: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="flex flex-col gap-1.5">
        <span class="text-sm font-medium first-letter:uppercase">{{ label }}</span>
        <textarea
            :name="name"
            :rows="rows"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :class="[
                'rounded-xl border bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                error ? 'border-brand-400' : 'border-line',
            ]"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        ></textarea>
        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else-if="help" class="text-xs text-ink-muted">{{ help }}</span>
    </label>
</template>
