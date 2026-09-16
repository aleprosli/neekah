<script setup>
defineProps({
    label: { type: String, required: true },
    name: { type: String, required: true },
    modelValue: { type: [String, Number, null], default: '' },
    /** [{ value, label }] */
    options: { type: Array, required: true },
    placeholder: { type: String, default: null },
    help: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="flex flex-col gap-1.5">
        <span class="text-sm font-medium">{{ label }}</span>
        <select
            :name="name"
            :value="modelValue"
            :required="required"
            :class="[
                'rounded-xl border bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                error ? 'border-brand-400' : 'border-line',
            ]"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else-if="help" class="text-xs text-ink-muted">{{ help }}</span>
    </label>
</template>
