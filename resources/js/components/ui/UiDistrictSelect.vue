<script setup>
/**
 * The daerah of one negeri. The list and its label ("Daerah", "Jajahan",
 * "Kawasan") follow the negeri picked beside it, and a daerah left over from
 * another negeri is cleared rather than posted.
 */
import { computed, watch } from 'vue';
import UiSelect from './UiSelect.vue';

const props = defineProps({
    name: { type: String, default: 'district' },
    state: { type: String, default: '' },
    /** { [negeri]: { label, options: [daerah] } } */
    districts: { type: Object, required: true },
    modelValue: { type: String, default: '' },
    help: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const area = computed(() => props.districts[props.state] ?? null);
const options = computed(() => (area.value?.options ?? []).map((name) => ({ value: name, label: name })));

watch(
    () => props.state,
    () => {
        if (props.modelValue && !options.value.some((option) => option.value === props.modelValue)) {
            emit('update:modelValue', '');
        }
    },
);
</script>

<template>
    <UiSelect
        :model-value="modelValue"
        :label="area?.label ?? $t('vendor_signup.district')"
        :name="name"
        :options="options"
        :placeholder="state ? $t('vendor_signup.district_placeholder', { area: (area?.label ?? '').toLowerCase() }) : $t('vendor_signup.district_pick_state')"
        :help="help"
        :error="error"
        :required="required"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>
