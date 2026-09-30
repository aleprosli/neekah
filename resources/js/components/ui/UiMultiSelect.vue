<script setup>
/**
 * A dropdown you can pick several things from.
 *
 * Thirteen categories or sixteen negeri as a wrap of checkboxes runs off the
 * bottom of a phone, so the list lives in a popup and what you picked sits
 * above it as chips you can take off again. Each row says "Dipilih" once it is
 * in, and the values post as ordinary name[] inputs.
 */
import { computed } from 'vue';
import { useAnchoredMenu } from '../../composables/useAnchoredMenu.js';

const props = defineProps({
    label: { type: String, required: true },
    /** Posts as an array, so pass it with the brackets: "service_states[]". */
    name: { type: String, required: true },
    /** [{ value, label, flag, icon, locked, note }] — a locked option cannot be taken off. */
    options: { type: Array, required: true },
    modelValue: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Pilih' },
    help: { type: String, default: null },
    error: { type: String, default: null },
    max: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue']);

const { root, trigger, menu, open, style, target, close, toggle } = useAnchoredMenu();

const same = (a, b) => String(a) === String(b);

const locked = computed(() => props.options.filter((option) => option.locked).map((option) => option.value));

/** A locked option counts as chosen even when the form never posted it. */
const values = computed(() => {
    const chosen = [...props.modelValue, ...locked.value];

    return props.options.filter((option) => chosen.some((value) => same(value, option.value))).map((option) => option.value);
});

const chosen = computed(() => props.options.filter((option) => values.value.some((value) => same(value, option.value))));

const isChosen = (option) => values.value.some((value) => same(value, option.value));

const isFull = computed(() => props.max !== null && values.value.length >= props.max);

const choose = (option) => {
    if (option.locked) return;
    if (!isChosen(option) && isFull.value) return;

    emit(
        'update:modelValue',
        isChosen(option)
            ? props.modelValue.filter((value) => !same(value, option.value))
            : [...props.modelValue, option.value],
    );
};

const remove = (option) => {
    if (option.locked) return;

    emit('update:modelValue', props.modelValue.filter((value) => !same(value, option.value)));
};

const triggerLabel = computed(() => (chosen.value.length ? `${chosen.value.length} dipilih` : props.placeholder));
</script>

<template>
    <div ref="root" class="flex min-w-0 flex-col gap-1.5">
        <span class="text-sm font-medium first-letter:uppercase">{{ label }}</span>

        <ul v-if="chosen.length" class="flex flex-wrap gap-2">
            <li
                v-for="option in chosen"
                :key="option.value"
                class="flex max-w-full items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 py-1 pr-1 pl-3 text-sm text-brand-800"
            >
                <img v-if="option.flag" :src="option.flag" alt="" loading="lazy" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-black/10">
                <span class="min-w-0 truncate">{{ option.label }}</span>
                <span v-if="option.note" class="shrink-0 text-[11px] text-brand-600">{{ option.note }}</span>
                <button
                    v-if="option.locked"
                    type="button"
                    disabled
                    class="size-6 shrink-0 opacity-0"
                    aria-hidden="true"
                ></button>
                <button
                    v-else
                    type="button"
                    class="flex size-6 shrink-0 items-center justify-center rounded-full text-brand-600 transition hover:bg-brand-100 hover:text-brand-800"
                    :aria-label="`Buang ${option.label}`"
                    @click="remove(option)"
                >
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </li>
        </ul>

        <input v-for="value in values" :key="value" type="hidden" :name="name" :value="value">

        <button
            ref="trigger"
            type="button"
            :class="[
                'flex w-full cursor-pointer items-center gap-2 rounded-xl border bg-surface px-4 py-2.5 text-left text-sm transition focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                error ? 'border-brand-400' : 'border-line focus:border-brand-400',
            ]"
            :aria-expanded="open"
            aria-haspopup="listbox"
            @click="toggle"
            @keydown.esc="close"
        >
            <span :class="['min-w-0 flex-1 truncate', chosen.length ? '' : 'text-ink-muted']">{{ triggerLabel }}</span>
            <svg class="size-4 shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <Teleport :to="target">
            <ul
                v-if="open"
                ref="menu"
                role="listbox"
                aria-multiselectable="true"
                :style="style"
                class="z-50 overflow-y-auto rounded-xl border border-line bg-surface-raised py-1 shadow-xl shadow-brand-900/10"
            >
                <li
                    v-for="option in options"
                    :key="option.value"
                    role="option"
                    :aria-selected="isChosen(option)"
                    :class="[
                        'flex items-center gap-2.5 px-3 py-2.5 text-sm transition',
                        option.locked ? 'cursor-default' : 'cursor-pointer',
                        !isChosen(option) && isFull ? 'cursor-not-allowed opacity-40' : '',
                        isChosen(option) ? 'text-brand-700' : 'hover:bg-surface-muted',
                    ]"
                    @click="choose(option)"
                >
                    <img v-if="option.flag" :src="option.flag" alt="" loading="lazy" class="h-3.5 w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-black/10">
                    <span v-else-if="option.icon" class="w-5 shrink-0 text-center" aria-hidden="true">{{ option.icon }}</span>
                    <span class="min-w-0 flex-1 truncate">{{ option.label }}</span>
                    <span v-if="option.locked" class="shrink-0 text-[11px] text-ink-muted">{{ option.note ?? 'wajib' }}</span>
                    <span v-else-if="isChosen(option)" class="flex shrink-0 items-center gap-1 text-[11px] font-semibold">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m20 6-11 11-5-5"/></svg>
                        Dipilih
                    </span>
                </li>
            </ul>
        </Teleport>

        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else-if="help" class="text-xs text-ink-muted">{{ help }}</span>
    </div>
</template>
