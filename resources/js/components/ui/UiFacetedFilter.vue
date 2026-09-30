<script setup>
/**
 * One filter of a table as a compact button that opens a checklist: tick as
 * many values as you like, search when the list is long, see how many rows
 * each value has. Ten of these sit on one line; the old chip rows stacked a
 * line per filter and took one value each.
 *
 * Built on Reka UI's Popover and Listbox, so it is keyboard and screen
 * reader friendly, and styled here with the site's own Tailwind tokens.
 * `multiple: false` makes it pick one value (the users page's segment
 * views, which swap the table's columns).
 */
import { ListboxContent, ListboxItem, ListboxRoot, PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui';
import { computed, ref } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    /** [{ value, label, count?, description? }] */
    options: { type: Array, required: true },
    modelValue: { type: Array, default: () => [] },
    multiple: { type: Boolean, default: true },
    hint: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const searchable = computed(() => props.options.length > 7);
const shown = computed(() => {
    const needle = query.value.trim().toLowerCase();

    return needle ? props.options.filter((option) => String(option.label).toLowerCase().includes(needle)) : props.options;
});
const chosen = computed(() => props.options.filter((option) => props.modelValue.includes(option.value)));

const pick = (value) => {
    if (!props.multiple) {
        emit('update:modelValue', props.modelValue.includes(value) ? [] : [value]);
        open.value = false;

        return;
    }

    emit('update:modelValue', props.modelValue.includes(value) ? props.modelValue.filter((one) => one !== value) : [...props.modelValue, value]);
};

const clear = () => {
    emit('update:modelValue', []);
    open.value = false;
};
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverTrigger
            :class="[
                'inline-flex h-9 shrink-0 items-center gap-2 rounded-full border px-3.5 text-sm font-medium whitespace-nowrap transition',
                chosen.length ? 'border-brand-300 bg-brand-50 text-brand-800' : 'border-dashed border-line text-ink hover:border-brand-400',
            ]"
            :title="hint"
        >
            <svg class="size-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path v-if="chosen.length" d="M4 6h16M7 12h10M10 18h4" />
                <template v-else><circle cx="12" cy="12" r="9" /><path d="M12 8v8M8 12h8" /></template>
            </svg>
            {{ label }}
            <template v-if="chosen.length">
                <span class="h-4 w-px bg-brand-200" aria-hidden="true"></span>
                <span v-if="chosen.length <= 2" class="flex gap-1">
                    <span v-for="option in chosen" :key="option.value" class="max-w-32 truncate rounded-md bg-surface-raised px-1.5 py-0.5 text-xs">{{ option.label }}</span>
                </span>
                <span v-else class="rounded-md bg-surface-raised px-1.5 py-0.5 text-xs">{{ $t('common.filter_selected', { count: chosen.length }) }}</span>
            </template>
        </PopoverTrigger>

        <PopoverPortal>
            <PopoverContent align="start" :side-offset="6" class="z-50 w-72 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-line bg-surface-raised shadow-xl outline-none">
                <div v-if="searchable" class="border-b border-line p-2">
                    <input v-model="query" type="search" :placeholder="$t('common.filter_search', { label })" class="w-full rounded-xl bg-surface-muted px-3 py-2 text-sm focus:outline-none" @keydown.stop>
                </div>

                <ListboxRoot :model-value="multiple ? modelValue : (modelValue[0] ?? null)" :multiple="multiple" selection-behavior="toggle" class="flex flex-col">
                    <ListboxContent class="max-h-72 overflow-y-auto p-1.5">
                        <p v-if="!shown.length" class="px-3 py-6 text-center text-sm text-ink-muted">{{ $t('common.filter_none') }}</p>
                        <ListboxItem
                            v-for="option in shown"
                            :key="option.value"
                            :value="option.value"
                            class="flex cursor-pointer items-start gap-3 rounded-xl px-3 py-2 text-sm outline-none select-none data-[highlighted]:bg-surface-muted"
                            @select.prevent="pick(option.value)"
                        >
                            <span
                                :class="[
                                    'mt-0.5 flex size-4 shrink-0 items-center justify-center border text-[10px] text-white',
                                    multiple ? 'rounded' : 'rounded-full',
                                    modelValue.includes(option.value) ? 'border-brand-600 bg-brand-600' : 'border-line bg-surface',
                                ]"
                                aria-hidden="true"
                            >{{ modelValue.includes(option.value) ? '✓' : '' }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate">{{ option.label }}</span>
                                <span v-if="option.description" class="block text-xs text-ink-muted">{{ option.description }}</span>
                            </span>
                            <span v-if="option.count !== null && option.count !== undefined" class="shrink-0 text-xs text-ink-muted tabular-nums">{{ option.count }}</span>
                        </ListboxItem>
                    </ListboxContent>
                </ListboxRoot>

                <button v-if="chosen.length" type="button" class="w-full border-t border-line px-3 py-2.5 text-center text-sm font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink" @click="clear">{{ $t('common.filter_clear') }}</button>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
