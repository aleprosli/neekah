<script setup>
/**
 * Which columns a table shows, ticked on and off from one small menu. The
 * table remembers the choice per list in this browser.
 */
import { DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuItemIndicator, DropdownMenuPortal, DropdownMenuRoot, DropdownMenuTrigger } from 'reka-ui';

defineProps({
    /** [{ key, label, visible }] */
    columns: { type: Array, required: true },
});

const emit = defineEmits(['toggle']);
</script>

<template>
    <DropdownMenuRoot>
        <DropdownMenuTrigger class="inline-flex h-9 shrink-0 items-center gap-2 rounded-full border border-line px-3.5 text-sm font-medium whitespace-nowrap transition hover:border-brand-400">
            <svg class="size-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 5h4v14H4zM10 5h4v14h-4zM16 5h4v14h-4z" /></svg>
            {{ $t('common.columns') }}
        </DropdownMenuTrigger>
        <DropdownMenuPortal>
            <DropdownMenuContent align="end" :side-offset="6" class="z-50 max-h-80 w-56 overflow-y-auto rounded-2xl border border-line bg-surface-raised p-1.5 shadow-xl outline-none">
                <DropdownMenuCheckboxItem
                    v-for="column in columns"
                    :key="column.key"
                    :model-value="column.visible"
                    class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 text-sm outline-none select-none data-[highlighted]:bg-surface-muted"
                    @update:model-value="emit('toggle', column.key)"
                    @select.prevent
                >
                    <span :class="['flex size-4 shrink-0 items-center justify-center rounded border text-[10px] text-white', column.visible ? 'border-brand-600 bg-brand-600' : 'border-line bg-surface']">
                        <DropdownMenuItemIndicator>✓</DropdownMenuItemIndicator>
                    </span>
                    <span class="truncate">{{ column.label }}</span>
                </DropdownMenuCheckboxItem>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
