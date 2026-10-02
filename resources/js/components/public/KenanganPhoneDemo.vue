<script setup>
/**
 * How Neekah Kenangan works, told by a phone on the About page.
 *
 * The phone plays the four steps a guest goes through (scan the table QR,
 * share photos, leave a wish, the couple keeps it all) and each step's card
 * slides out of it in turn, then the whole thing starts over. It only plays
 * while it is on screen. With reduced motion every card is shown at once and
 * the phone changes only when a card is chosen. Choosing a card shows its
 * screen and carries on from there.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    /** [{key, title, body}] in the order a guest meets them. */
    steps: { type: Array, required: true },
});

const STEP_MS = 2800;
const HOLD_MS = 4500;

const root = ref(null);
const active = ref(0);
const shown = ref(0);
const reducedMotion = ref(false);

let timer = null;
let observer = null;
let visible = false;

const stop = () => {
    clearTimeout(timer);
    timer = null;
};

const tick = () => {
    stop();
    if (!visible || reducedMotion.value) return;

    if (shown.value < props.steps.length) {
        active.value = shown.value;
        shown.value++;
        timer = setTimeout(tick, shown.value === props.steps.length ? HOLD_MS : STEP_MS);
    } else {
        shown.value = 0;
        timer = setTimeout(tick, 600);
    }
};

const choose = (index) => {
    active.value = index;
    shown.value = Math.max(shown.value, index + 1);
    stop();
    if (!reducedMotion.value) timer = setTimeout(tick, HOLD_MS);
};

onMounted(() => {
    reducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion.value || !('IntersectionObserver' in window)) {
        shown.value = props.steps.length;
        return;
    }

    observer = new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        if (visible && !timer) tick();
        if (!visible) stop();
    }, { threshold: 0.35 });
    observer.observe(root.value);
});

onBeforeUnmount(() => {
    stop();
    observer?.disconnect();
});

const tiles = ['from-brand-200 to-brand-400', 'from-gold-300 to-gold-500', 'from-brand-100 to-gold-300', 'from-gold-300 to-brand-300', 'from-brand-300 to-brand-500', 'from-gold-400 to-brand-200'];
</script>

<template>
    <div ref="root" class="grid items-center gap-6 sm:grid-cols-[200px_minmax(0,1fr)]">
        <!-- The phone. -->
        <div class="relative mx-auto w-[200px]" aria-hidden="true">
            <div class="absolute -inset-6 rounded-full bg-linear-to-br from-brand-100/70 to-gold-300/40 blur-2xl"></div>
            <div class="relative aspect-[9/19] rounded-[2.2rem] bg-ink p-2 shadow-2xl shadow-brand-900/30 ring-1 ring-black/10">
                <div class="relative size-full overflow-hidden rounded-[1.7rem] bg-ivory">
                    <div class="absolute top-1.5 left-1/2 z-10 h-4 w-16 -translate-x-1/2 rounded-full bg-ink"></div>

                    <!-- 1. Scanning the QR on the table. -->
                    <div :class="['kpd-screen bg-ink', active === 0 && 'kpd-on']">
                        <div class="absolute inset-0 bg-linear-to-b from-brand-900/60 via-ink to-ink"></div>
                        <div class="absolute inset-x-6 top-1/2 aspect-square -translate-y-1/2">
                            <div class="absolute inset-3 rounded-lg bg-white p-2.5">
                                <div class="grid size-full grid-cols-5 grid-rows-5 gap-1">
                                    <span v-for="n in 25" :key="n" :class="['rounded-[2px]', [1, 2, 4, 6, 8, 10, 11, 13, 15, 17, 19, 20, 22, 24, 25].includes(n) ? 'bg-ink' : 'bg-transparent']"></span>
                                </div>
                            </div>
                            <span class="absolute top-0 left-0 size-6 rounded-tl-xl border-t-[3px] border-l-[3px] border-gold-400"></span>
                            <span class="absolute top-0 right-0 size-6 rounded-tr-xl border-t-[3px] border-r-[3px] border-gold-400"></span>
                            <span class="absolute bottom-0 left-0 size-6 rounded-bl-xl border-b-[3px] border-l-[3px] border-gold-400"></span>
                            <span class="absolute right-0 bottom-0 size-6 rounded-br-xl border-r-[3px] border-b-[3px] border-gold-400"></span>
                            <span class="kpd-scan absolute inset-x-2 h-0.5 rounded-full bg-gold-400 shadow-[0_0_12px_2px] shadow-gold-400/70"></span>
                        </div>
                        <div class="absolute inset-x-0 bottom-8 flex justify-center">
                            <span class="size-11 rounded-full border-4 border-white/80 bg-white/20"></span>
                        </div>
                    </div>

                    <!-- 2. The guest page: shoot, pick, and photos going up. -->
                    <div :class="['kpd-screen flex flex-col gap-2.5 px-3 pt-8', active === 1 && 'kpd-on']">
                        <div class="rounded-xl border border-gold-300 bg-linear-to-br from-brand-50 to-white py-2.5 text-center">
                            <span class="mx-auto block h-1 w-10 rounded-full bg-gold-400"></span>
                            <span class="mx-auto mt-1.5 block h-2 w-16 rounded-full bg-ink/80"></span>
                        </div>
                        <div class="grid grid-cols-3 gap-1.5">
                            <span class="flex aspect-square items-center justify-center rounded-lg bg-linear-to-br from-brand-500 to-brand-700 text-white">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3.5"/></svg>
                            </span>
                            <span class="flex aspect-square items-center justify-center rounded-lg border border-line bg-white text-gold-600">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                            </span>
                            <span class="flex aspect-square items-center justify-center rounded-lg border border-line bg-white text-brand-600">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 13 5.2 3.5a.5.5 0 0 0 .8-.4V7.9a.5.5 0 0 0-.8-.4L16 11"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>
                            </span>
                        </div>
                        <div v-for="(tile, i) in tiles.slice(0, 3)" :key="tile" class="flex items-center gap-2 rounded-lg border border-line bg-white p-1.5">
                            <span :class="['size-7 shrink-0 rounded-md bg-linear-to-br', tile]"></span>
                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-muted">
                                <span class="kpd-progress block h-full rounded-full bg-brand-600" :style="{ animationDelay: `${i * 0.35}s` }"></span>
                            </span>
                        </div>
                    </div>

                    <!-- 3. A wish for the couple, written or spoken. -->
                    <div :class="['kpd-screen flex flex-col gap-2.5 px-3 pt-8', active === 2 && 'kpd-on']">
                        <div class="flex items-center gap-1.5">
                            <span class="flex size-6 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </span>
                            <span class="h-2 w-20 rounded-full bg-ink/80"></span>
                        </div>
                        <div class="flex flex-col gap-1.5 rounded-xl border border-line bg-white p-2.5">
                            <span class="kpd-type h-1.5 rounded-full bg-ink/25" style="--w: 100%"></span>
                            <span class="kpd-type h-1.5 rounded-full bg-ink/25" style="--w: 85%; animation-delay: .5s"></span>
                            <span class="kpd-type h-1.5 rounded-full bg-ink/25" style="--w: 55%; animation-delay: 1s"></span>
                        </div>
                        <div class="flex items-center justify-center gap-3 rounded-xl bg-white py-3">
                            <span class="flex size-10 items-center justify-center rounded-full bg-brand-600 text-white shadow-md shadow-brand-900/20">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><path d="M12 18v4"/></svg>
                            </span>
                            <span class="flex h-6 items-center gap-0.5">
                                <span v-for="n in 7" :key="n" class="kpd-wave w-1 rounded-full bg-gold-500" :style="{ animationDelay: `${n * 0.12}s` }"></span>
                            </span>
                        </div>
                        <span class="flex h-7 items-center justify-center rounded-full bg-brand-600">
                            <span class="h-1.5 w-14 rounded-full bg-white/80"></span>
                        </span>
                        <svg class="kpd-heart mx-auto size-6 text-brand-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-7.5-4.6-9.6-9.3C.9 8.3 3 4.5 6.6 4.5c2.1 0 3.6 1.2 5.4 3.2 1.8-2 3.3-3.2 5.4-3.2 3.6 0 5.7 3.8 4.2 7.2C19.5 16.4 12 21 12 21z"/></svg>
                    </div>

                    <!-- 4. The couple's album, ready to download. -->
                    <div :class="['kpd-screen flex flex-col gap-2.5 px-3 pt-8', active === 3 && 'kpd-on']">
                        <span class="h-2 w-16 rounded-full bg-ink/80"></span>
                        <div class="grid grid-cols-3 gap-1">
                            <span v-for="(tile, i) in [...tiles, ...tiles.slice(0, 3)]" :key="i" :class="['kpd-pop aspect-square rounded-md bg-linear-to-br', tile]" :style="{ animationDelay: `${i * 0.08}s` }"></span>
                        </div>
                        <span class="mt-auto mb-5 flex h-9 items-center justify-center gap-1.5 rounded-full bg-linear-to-br from-gold-400 to-gold-600 text-white shadow-md">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11m-4.5-4.5L12 15l4.5-4.5M5 20h14"/></svg>
                            <span class="text-[10px] font-bold tracking-wider">ZIP</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- The steps, each sliding out of the phone in turn. -->
        <ol class="flex min-w-0 flex-col gap-3">
            <li v-for="(step, index) in steps" :key="step.key">
                <button
                    type="button"
                    :class="[
                        'flex w-full min-w-0 items-start gap-3 rounded-2xl border p-3 text-left transition duration-500 ease-out',
                        index < shown ? 'translate-x-0 translate-y-0 scale-100 opacity-100' : '-translate-y-4 scale-95 opacity-0 sm:translate-y-0 sm:-translate-x-12',
                        index === active ? 'border-brand-300 bg-white shadow-lg shadow-brand-900/5' : 'border-transparent bg-white/60',
                    ]"
                    :aria-current="index === active ? 'step' : undefined"
                    @click="choose(index)"
                >
                    <span :class="['flex size-7 shrink-0 items-center justify-center rounded-full font-display text-sm font-semibold transition-colors', index === active ? 'bg-brand-600 text-white' : 'border border-gold-500 bg-ivory text-brand-700']">{{ index + 1 }}</span>
                    <span class="min-w-0 text-sm"><span class="block font-semibold">{{ step.title }}</span><span class="text-ink-muted">{{ step.body }}</span></span>
                </button>
            </li>
        </ol>
    </div>
</template>

<style scoped>
.kpd-screen {
    position: absolute;
    inset: 0;
    opacity: 0;
    transform: scale(0.96);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.kpd-on {
    opacity: 1;
    transform: none;
}

.kpd-scan {
    animation: kpd-scan 1.8s ease-in-out infinite alternate;
}

.kpd-progress {
    width: 0;
    animation: kpd-fill 2.2s ease-out forwards;
}

.kpd-type {
    width: 0;
    animation: kpd-type 0.8s ease-out forwards;
}

.kpd-wave {
    height: 30%;
    animation: kpd-wave 0.9s ease-in-out infinite alternate;
}

.kpd-heart {
    animation: kpd-heart 1.4s ease-in-out infinite;
}

.kpd-pop {
    animation: kpd-pop 0.4s ease-out backwards;
}

/* Animations restart each time a screen comes back on. */
.kpd-screen:not(.kpd-on) * {
    animation: none;
}

@keyframes kpd-scan {
    from { top: 12%; }
    to { top: 86%; }
}

@keyframes kpd-fill {
    to { width: 100%; }
}

@keyframes kpd-type {
    to { width: var(--w); }
}

@keyframes kpd-wave {
    to { height: 100%; }
}

@keyframes kpd-heart {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.18); }
}

@keyframes kpd-pop {
    from { opacity: 0; transform: scale(0.6); }
}

@media (prefers-reduced-motion: reduce) {
    .kpd-screen * {
        animation: none !important;
    }

    .kpd-progress, .kpd-type {
        width: var(--w, 100%);
    }
}
</style>
