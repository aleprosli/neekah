<script setup>
/**
 * The couple's countdown, in two sizes: a full-width card at the top of the
 * dashboard ("hero") and a compact one in the sidebar on every couple page,
 * which a phone shows in its menu.
 *
 * It never just disappears. Before the day it counts down (digits that roll
 * as they change) with how much of the checklist is done; on the day it celebrates; afterwards it counts the days married;
 * and a couple with no wedding yet is asked for their date instead. The
 * server renders the day count inside the mount element, so a page seen
 * before this loads still says how long is left.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    /** ISO moment the day begins (Wedding::startsAt), or null with no wedding. */
    target: { type: String, default: null },
    /** How much of the checklist is done, 0–100 (Wedding::planningProgress). */
    progress: { type: Number, default: 0 },
    /** Where a couple without a wedding sets their date. */
    createUrl: { type: String, default: null },
    /** The couple's names, the hero's heading (the dashboard has no other). */
    title: { type: String, default: null },
    /** "Ahad, 20 Disember 2026 · Alor Setar, Kedah", for the hero. */
    detail: { type: String, default: null },
    /** Buttons under the heading: [{ label, url, primary }]. */
    links: { type: Array, default: () => [] },
    variant: { type: String, default: 'hero' },
});

const now = ref(Date.now());
let timer = null;

onMounted(() => {
    timer = window.setInterval(() => (now.value = Date.now()), 1000);
});
onBeforeUnmount(() => timer && window.clearInterval(timer));

const targetAt = computed(() => (props.target ? new Date(props.target).getTime() : null));
const remaining = computed(() => (targetAt.value === null ? 0 : Math.max(0, targetAt.value - now.value)));

/** none (no wedding), upcoming, today (the day itself) or married (after it). */
const state = computed(() => {
    if (targetAt.value === null) return 'none';
    if (remaining.value > 0) return 'upcoming';

    return now.value - targetAt.value < 86_400_000 ? 'today' : 'married';
});

const daysMarried = computed(() => Math.max(1, Math.floor((now.value - targetAt.value) / 86_400_000)));

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

/** The sidebar ring: a 26px-radius circle, filled as far as the planning has come. */
const circumference = 2 * Math.PI * 26;
</script>

<template>
    <!-- Sidebar: compact, on every couple page -->
    <div v-if="variant === 'sidebar'" class="relative overflow-hidden rounded-2xl bg-linear-to-br from-brand-700 via-brand-800 to-brand-900 p-3 text-white shadow-md shadow-brand-900/20" role="timer">
        <div class="pointer-events-none absolute -top-8 -right-6 size-24 rounded-full bg-gold-300/20 blur-2xl" aria-hidden="true"></div>

        <a v-if="state === 'none'" :href="createUrl" class="relative flex flex-col gap-1 text-left">
            <span class="font-script text-xl leading-none text-gold-300">{{ $t('countdown.bila_hari_bahagia') }}</span>
            <span class="text-xs text-white/80">{{ $t('countdown.tetapkan_tarikh_ringkas') }}</span>
            <span class="mt-1 w-fit rounded-full bg-white/15 px-3 py-1 text-xs font-semibold ring-1 ring-white/25">{{ $t('countdown.tetapkan_tarikh') }} →</span>
        </a>

        <div v-else class="relative flex items-center gap-3">
            <div class="relative size-16 shrink-0">
                <svg class="size-16 -rotate-90" viewBox="0 0 60 60" aria-hidden="true">
                    <circle cx="30" cy="30" r="26" fill="none" stroke="currentColor" stroke-width="4" class="text-white/15" />
                    <circle cx="30" cy="30" r="26" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="text-gold-300 motion-safe:transition-[stroke-dashoffset] motion-safe:duration-700" :stroke-dasharray="circumference" :stroke-dashoffset="state === 'upcoming' ? circumference * (1 - progress / 100) : 0" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                    <span class="font-display text-xl font-semibold tabular-nums">{{ state === 'upcoming' ? cells[0].value : state === 'today' ? '♥' : daysMarried }}</span>
                    <span v-if="state !== 'today'" class="mt-0.5 text-[9px] tracking-wider text-gold-300 uppercase">{{ $t('countdown.hari') }}</span>
                </div>
            </div>

            <div class="min-w-0">
                <p class="font-script text-lg leading-none text-gold-300">{{ state === 'upcoming' ? $t('countdown.menuju_hari_bahagia') : state === 'today' ? $t('countdown.hari_bahagia') : $t('countdown.pengantin_baru') }}</p>
                <p v-if="state === 'upcoming'" class="mt-1.5 font-mono text-sm tracking-wide text-white tabular-nums">{{ pad(cells[1].value) }}:{{ pad(cells[2].value) }}:{{ pad(cells[3].value) }}</p>
                <p v-else-if="state === 'today'" class="mt-1 text-xs text-white/85">{{ $t('countdown.hari_ini') }}</p>
                <p v-else class="mt-1 text-xs text-white/85">{{ $t('countdown.hari_bergelar', { count: daysMarried }) }}</p>
                <p v-if="state === 'upcoming'" class="mt-0.5 text-[10px] text-white/70">{{ $t('countdown.persiapan', { percent: progress }) }}</p>
            </div>
        </div>
    </div>

    <!-- Hero: the top of the dashboard -->
    <section v-else-if="state !== 'none'" class="relative overflow-hidden rounded-[1.75rem] bg-linear-to-br from-brand-700 via-brand-800 to-brand-900 text-white shadow-lg shadow-brand-900/20" role="timer">
        <div class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-gold-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-28 -left-20 size-72 rounded-full bg-brand-400/30 blur-3xl" aria-hidden="true"></div>
        <svg class="pointer-events-none absolute -right-16 -bottom-20 size-56 text-gold-300/10 sm:size-72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.6" aria-hidden="true"><circle cx="9" cy="14" r="5.5" /><circle cx="16" cy="14" r="5.5" /><path d="m9 5 1.5 2.5h-3L9 5Z" /></svg>

        <div class="relative grid items-center gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-x-10 lg:gap-y-5">
            <div class="min-w-0 text-center lg:col-start-1 lg:row-start-1 lg:self-end lg:text-left">
                <p class="font-script text-2xl leading-none text-gold-300 sm:text-3xl">{{ state === 'upcoming' ? $t('countdown.menuju_hari_bahagia') : state === 'today' ? $t('countdown.hari_bahagia') : $t('countdown.selamat_pengantin_baru') }}</p>
                <h1 v-if="title" class="mt-2 font-display text-3xl leading-tight font-semibold break-words sm:text-4xl">{{ title }}</h1>
                <p v-if="detail" class="mt-2 text-sm text-white/80">{{ detail }}</p>

                <div v-if="state === 'upcoming'" class="mx-auto mt-5 max-w-sm lg:mx-0">
                    <div class="flex items-center justify-between text-xs text-white/75">
                        <span>{{ $t('countdown.perjalanan_persiapan') }}</span>
                        <span class="font-semibold text-gold-300 tabular-nums">{{ progress }}%</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-white/15">
                        <div class="h-full rounded-full bg-linear-to-r from-gold-300 to-gold-400 motion-safe:transition-[width] motion-safe:duration-700" :style="{ width: `${progress}%` }"></div>
                    </div>
                </div>

            </div>

            <!-- Digits that roll as they change -->
            <div v-if="state === 'upcoming'" class="grid grid-cols-4 gap-2 sm:gap-3 lg:col-start-2 lg:row-span-2 lg:row-start-1">
                <div v-for="cell in cells" :key="cell.key" class="flex min-w-0 flex-col items-center rounded-2xl bg-white/10 px-1 py-3 ring-1 ring-white/15 backdrop-blur-sm sm:min-w-20 sm:px-3 sm:py-4">
                    <span class="relative block h-9 overflow-hidden sm:h-12" aria-hidden="true">
                        <Transition
                            enter-active-class="motion-safe:transition motion-safe:duration-300 motion-safe:ease-out"
                            enter-from-class="motion-safe:translate-y-full motion-safe:opacity-0"
                            leave-active-class="motion-safe:transition motion-safe:duration-300 motion-safe:ease-in absolute inset-x-0"
                            leave-to-class="motion-safe:-translate-y-full motion-safe:opacity-0"
                        >
                            <span :key="cell.value" class="block font-display text-3xl leading-9 font-semibold tabular-nums sm:text-5xl sm:leading-[3rem]">{{ cell.key === 'hari' ? cell.value : pad(cell.value) }}</span>
                        </Transition>
                    </span>
                    <span class="sr-only">{{ cell.value }}</span>
                    <span class="mt-1.5 text-[10px] font-medium tracking-[0.18em] text-gold-300 uppercase sm:text-xs">{{ $t(`countdown.${cell.key}`) }}</span>
                </div>
            </div>

            <div v-else class="flex flex-col items-center rounded-2xl bg-white/10 px-8 py-5 text-center ring-1 ring-white/15 backdrop-blur-sm lg:col-start-2 lg:row-span-2 lg:row-start-1">
                <span v-if="state === 'today'" class="font-display text-4xl" aria-hidden="true">♥</span>
                <span v-else class="font-display text-5xl font-semibold tabular-nums">{{ daysMarried }}</span>
                <span class="mt-2 text-xs tracking-wide text-gold-300">{{ state === 'today' ? $t('countdown.hari_ini') : $t('countdown.hari_bergelar', { count: daysMarried }) }}</span>
            </div>
            <div v-if="links.length" class="flex flex-wrap justify-center gap-2 lg:col-start-1 lg:row-start-2 lg:self-start lg:justify-start">
                <a
                    v-for="link in links"
                    :key="link.url"
                    :href="link.url"
                    :class="link.primary
                        ? 'rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-brand-800 shadow-sm transition hover:bg-gold-300'
                        : 'rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/40 transition hover:bg-white/10'"
                >{{ link.label }}</a>
            </div>
        </div>
    </section>
</template>
