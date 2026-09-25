<script setup>
/**
 * A phone field with a country picker (every country, flags, search), built
 * on intl-tel-input. Malaysia is the default and sits at the top with its
 * neighbours, because nearly everyone signing up is Malaysian.
 *
 * The visible input has no name. What the form posts is the hidden `name`
 * input: the number in E.164 ("+60123456789") once it reads as valid, or
 * whatever was typed otherwise, so the server's phone rule gives the error.
 */
import 'intl-tel-input/styles';
import intlTelInput from 'intl-tel-input';
import msTranslations from 'intl-tel-input/locale/ms';
import { onBeforeUnmount, onMounted, ref, useId } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    name: { type: String, required: true },
    modelValue: { type: String, default: '' },
    help: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const id = useId();
const input = ref(null);
const posted = ref(props.modelValue ?? '');
let iti = null;

const sync = () => {
    const typed = input.value?.value ?? '';
    const number = iti?.isValidNumber() ? iti.getNumber() : typed;

    posted.value = number;
    emit('update:modelValue', number);
};

onMounted(() => {
    const malay = document.documentElement.lang === 'ms';

    iti = intlTelInput(input.value, {
        initialCountry: 'my',
        countryOrder: ['my', 'sg', 'bn', 'id', 'th'],
        countryNameLocale: malay ? 'ms' : 'en',
        uiTranslations: malay ? msTranslations : {},
        containerClass: 'w-full',
        loadUtils: () => import('intl-tel-input/utils'),
    });

    if (props.modelValue) {
        iti.setNumber(props.modelValue);
    }

    iti.promise.then(sync);
    input.value.addEventListener('countrychange', sync);
});

onBeforeUnmount(() => iti?.destroy());
</script>

<template>
    <!-- A div, not a label: a label would hand its clicks to the country button. -->
    <div class="flex flex-col gap-1.5">
        <label :for="id" class="text-sm font-medium">{{ label }}</label>
        <input
            :id="id"
            ref="input"
            type="tel"
            autocomplete="tel"
            :required="required"
            :class="[
                'w-full rounded-xl border bg-surface py-2.5 pr-4 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                error ? 'border-brand-400' : 'border-line',
            ]"
            @input="sync"
        >
        <input type="hidden" :name="name" :value="posted">
        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else-if="help" class="text-xs text-ink-muted">{{ help }}</span>
    </div>
</template>
