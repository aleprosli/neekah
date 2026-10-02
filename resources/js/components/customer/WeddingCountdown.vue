<script setup>
/**
 * Days, hours, minutes and seconds to the wedding, ticking. The sidebar carries
 * a compact one on every page and the dashboard a large one beside the title;
 * the server renders the day count inside the mount element, so a page seen
 * before this loads still says how long is left.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    target: { type: String, required: true },
    variant: { type: String, default: 'hero' },
});

const remaining = ref(Math.max(0, new Date(props.target).getTime() - Date.now()));
let timer = null;

const tick = () => {
    remaining.value = Math.max(0, new Date(props.target).getTime() - Date.now());
};

onMounted(() => {
    timer = window.setInterval(tick, 1000);
});

onBeforeUnmount(() => timer && window.clearInterval(timer));

const arrived = computed(() => remaining.value === 0);

const cells = computed(() => {
    const seconds = Math.floor(remaining.value / 1000);

    return [
        { key: 'hari', value: Math.floor(seconds / 86400) },
        { key: 'jam', value: Math.floor((seconds % 86400) / 3600) },
        { key: 'minit', value: Math.floor((seconds % 3600) / 60) },
        { key: 'saat', value: seconds % 60 },
    ];
});

const pad = (value) => String(value).padStart(2, '0');
</script>

<template>
    <div v-if="variant === 'sidebar'" class="rounded-2xl border border-gold-300/60 bg-surface-raised/80 px-3 py-2.5 text-center" role="timer">
        <p class="font-display text-[12px] text-gold-600 italic">{{ $t('countdown.menuju_hari_bahagia') }}</p>
        <p v-if="arrived" class="mt-1 text-sm font-semibold text-brand-700">{{ $t('countdown.hari_ini') }}</p>
        <div v-else class="mt-1 grid grid-cols-4 gap-1">
            <div v-for="cell in cells" :key="cell.key">
                <p class="font-display text-lg leading-none font-semibold text-brand-700 tabular-nums">{{ pad(cell.value) }}</p>
                <p class="mt-0.5 text-[10px] tracking-wide text-ink-muted uppercase">{{ $t(`countdown.${cell.key}`) }}</p>
            </div>
        </div>
    </div>

    <div v-else class="rounded-2xl border border-gold-300/70 bg-surface-raised px-5 py-3 shadow-sm shadow-brand-900/5" role="timer">
        <p class="text-center font-display text-sm text-gold-600 italic">{{ $t('countdown.menuju_hari_bahagia') }}</p>
        <p v-if="arrived" class="mt-1 text-center font-display text-xl font-semibold text-brand-700">{{ $t('countdown.hari_ini') }}</p>
        <div v-else class="mt-1.5 flex items-start justify-center gap-2 sm:gap-3">
            <template v-for="(cell, at) in cells" :key="cell.key">
                <span v-if="at > 0" class="pt-1 font-display text-2xl text-gold-400" aria-hidden="true">:</span>
                <div class="min-w-12 text-center">
                    <p class="font-display text-3xl leading-none font-semibold text-brand-700 tabular-nums">{{ pad(cell.value) }}</p>
                    <p class="mt-1 text-[11px] tracking-wide text-ink-muted uppercase">{{ $t(`countdown.${cell.key}`) }}</p>
                </div>
            </template>
        </div>
    </div>
</template>
