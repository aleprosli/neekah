<script setup>
/**
 * A dropdown that shows a flag beside each name.
 *
 * A native select can only hold text, so this is a listbox: a button, a
 * popup and a hidden input carrying the value, which keeps it working inside
 * an ordinary GET or POST form. Blade mounts it over a real select, so a
 * visitor without JavaScript still gets the plain field.
 */
import { computed, ref, watch } from 'vue';
import { useAnchoredMenu } from '../../composables/useAnchoredMenu.js';

const props = defineProps({
    label: { type: String, default: null },
    name: { type: String, required: true },
    /** [{ value, label, flag }] — flag is a URL, or null for no artwork. */
    options: { type: Array, required: true },
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: null },
    help: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    /** 'field' sits in a form; 'bare' sits inside the search bar's own box. */
    variant: { type: String, default: 'field' },
    /** The marketplace filters apply the moment you pick. */
    submitOnChange: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const selected = ref(props.modelValue ?? '');
const activeIndex = ref(-1);

const { root, trigger, menu, open, style, target, close, toggle: toggleMenu } = useAnchoredMenu();

watch(
    () => props.modelValue,
    (value) => {
        selected.value = value ?? '';
    },
);

const choices = computed(() =>
    props.placeholder ? [{ value: '', label: props.placeholder, flag: null }, ...props.options] : props.options,
);

const current = computed(() => choices.value.find((option) => option.value === selected.value) ?? choices.value[0]);

const choose = (option) => {
    selected.value = option.value;
    close();
    emit('update:modelValue', option.value);

    if (props.submitOnChange) {
        // The hidden input has to carry the new value before the form reads it.
        requestAnimationFrame(() => root.value?.closest('form')?.requestSubmit());
    }
};

const toggle = () => {
    toggleMenu();
    activeIndex.value = open.value ? choices.value.findIndex((option) => option.value === selected.value) : -1;
};

const onKeydown = (event) => {
    if (event.key === 'Escape') {
        close();
        return;
    }

    if (!open.value && ['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
        event.preventDefault();
        toggle();
        return;
    }

    if (!open.value) return;

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        const step = event.key === 'ArrowDown' ? 1 : -1;
        activeIndex.value = (activeIndex.value + step + choices.value.length) % choices.value.length;
    }

    if (event.key === 'Enter' && activeIndex.value >= 0) {
        event.preventDefault();
        choose(choices.value[activeIndex.value]);
    }
};
</script>

<template>
    <div ref="root" :class="['relative', variant === 'bare' ? 'flex flex-col gap-0.5' : 'flex flex-col gap-1.5']">
        <span v-if="label && variant === 'bare'" class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase">{{ label }}</span>
        <span v-else-if="label" class="text-sm font-medium first-letter:uppercase">{{ label }}</span>

        <input type="hidden" :name="name" :value="selected">

        <button
            ref="trigger"
            type="button"
            :class="[
                'flex w-full cursor-pointer items-center gap-2 text-left text-sm',
                variant === 'bare'
                    ? 'bg-transparent font-medium focus:outline-none'
                    : [
                          'rounded-xl border bg-surface px-4 py-2.5 transition focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                          error ? 'border-brand-400' : 'border-line focus:border-brand-400',
                      ],
            ]"
            :aria-expanded="open"
            :aria-required="required"
            aria-haspopup="listbox"
            @click="toggle"
            @keydown="onKeydown"
        >
            <img v-if="current?.flag" :src="current.flag" alt="" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-black/10">
            <span class="min-w-0 flex-1 truncate">{{ current?.label }}</span>
            <svg class="size-4 shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <Teleport :to="target">
            <ul
                v-if="open"
                ref="menu"
                role="listbox"
                :style="style"
                class="z-50 overflow-y-auto rounded-xl border border-line bg-surface-raised py-1 shadow-xl shadow-brand-900/10"
            >
                <li
                    v-for="(option, index) in choices"
                    :key="option.value"
                    role="option"
                    :aria-selected="option.value === selected"
                    :class="[
                        'flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm transition',
                        option.value === selected ? 'bg-brand-50 font-semibold text-brand-700' : '',
                        index === activeIndex && option.value !== selected ? 'bg-surface-muted' : '',
                    ]"
                    @click="choose(option)"
                    @mousemove="activeIndex = index"
                >
                    <img v-if="option.flag" :src="option.flag" alt="" loading="lazy" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-black/10">
                    <span v-else class="h-3.5 w-5 shrink-0" aria-hidden="true"></span>
                    <span class="min-w-0 truncate">{{ option.label }}</span>
                </li>
            </ul>
        </Teleport>

        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else-if="help" class="text-xs text-ink-muted">{{ help }}</span>
    </div>
</template>
