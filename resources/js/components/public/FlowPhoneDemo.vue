<script setup>
/**
 * "How it works" on the About page, played on one phone.
 *
 * The phone shows the step's number and name over a made-up Neekah screen
 * showing the step being done (FlowPhoneScreen). As each step plays its card slides out of the phone, steps one to three
 * to the left and four to six to the right (below it on a phone), and when
 * all six are out it holds, then starts over. It only plays while on screen.
 * With reduced motion every card is shown at once and the phone changes only
 * when a card is chosen. Choosing a card shows that step and carries on from there.
 *
 * The drawings arrive as SVG markup rendered by the Blade component
 * site/flow-illustration, so the server fallback and the phone share one set.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FlowPhoneScreen from './FlowPhoneScreen.vue';

const props = defineProps({
    /** [{key, label, description, number, illustration}] in order. */
    steps: { type: Array, required: true },
    /** The made-up words on the phone's screens (pages.landing.flow_demo). */
    text: { type: Object, required: true },
});

const STEP_MS = 3200;
/** Steps whose screen has more to play than the usual time allows. */
const STEP_MS_BY_KEY = { deal: 4400 };
const HOLD_MS = 4500;
/** How long the cards take to fold back into the phone before the next round. */
const REWIND_MS = 1100;

const root = ref(null);
const active = ref(0);
const shown = ref(0);
const reducedMotion = ref(false);
/** Which way the phone's screen slides: forward to a later step, or back to an earlier one. */
const direction = ref('forward');

let timer = null;
let observer = null;
let visible = false;

const half = Math.ceil(props.steps.length / 2);
const sides = computed(() => [
    props.steps.slice(0, half).map((step, i) => ({ step, index: i })),
    props.steps.slice(half).map((step, i) => ({ step, index: i + half })),
]);


const stop = () => {
    clearTimeout(timer);
    timer = null;
};

const tick = () => {
    stop();
    if (!visible || reducedMotion.value) return;

    if (shown.value < props.steps.length) {
        go(shown.value);
        shown.value++;
        timer = setTimeout(tick, shown.value === props.steps.length ? HOLD_MS : (STEP_MS_BY_KEY[props.steps[active.value].key] ?? STEP_MS));
    } else {
        // Fold the cards back in, last first, then start again from step one.
        shown.value = 0;
        timer = setTimeout(() => {
            go(0);
            timer = setTimeout(tick, 700);
        }, REWIND_MS);
    }
};

const go = (index) => {
    direction.value = index < active.value ? 'back' : 'forward';
    active.value = index;
};

const choose = (index) => {
    go(index);
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
</script>

<template>
    <div ref="root" class="grid items-center gap-6 lg:grid-cols-[minmax(0,1fr)_270px_minmax(0,1fr)] lg:gap-10">
        <!-- The phone. -->
        <div class="relative mx-auto w-[270px] lg:col-start-2 lg:row-start-1" aria-hidden="true">
            <div class="absolute -inset-8 rounded-full bg-linear-to-br from-brand-100/70 to-gold-300/40 blur-2xl"></div>
            <div class="relative rounded-[2.4rem] bg-ink p-2 shadow-2xl shadow-brand-900/30 ring-1 ring-black/10">
                <div class="relative flex h-[560px] flex-col overflow-hidden rounded-[1.9rem] bg-ivory">
                    <div class="flex h-8 shrink-0 items-center justify-between px-6 pt-1 text-[10px] font-semibold">
                        <span>9:30</span>
                        <span class="absolute top-2 left-1/2 h-5 w-20 -translate-x-1/2 rounded-full bg-ink"></span>
                        <span class="flex items-center gap-1">
                            <svg class="size-3" viewBox="0 0 12 12" fill="currentColor"><rect x="0" y="8" width="2" height="4" rx=".5"/><rect x="3.3" y="6" width="2" height="6" rx=".5"/><rect x="6.6" y="3.5" width="2" height="8.5" rx=".5"/><rect x="9.9" y="1" width="2" height="11" rx=".5"/></svg>
                            <svg class="h-3 w-5" viewBox="0 0 20 10" fill="none" stroke="currentColor"><rect x=".5" y=".5" width="16" height="9" rx="2"/><rect x="2" y="2" width="11" height="6" rx="1" fill="currentColor" stroke="none"/><path d="M18.5 3.5v3" stroke-linecap="round"/></svg>
                        </span>
                    </div>

                    <!-- The step's own screen: the old one slides out before the new one comes in. -->
                    <div class="flex shrink-0 items-baseline gap-2 px-4 pt-2 pb-2">
                        <Transition :name="`fpd-screen-${direction}`" mode="out-in">
                            <p :key="active" class="flex items-baseline gap-2">
                                <span class="text-[10px] font-semibold tracking-[0.2em] text-brand-600 uppercase">{{ steps[active].number }}</span>
                                <span class="font-display text-sm font-semibold">{{ steps[active].label }}</span>
                            </p>
                        </Transition>
                    </div>
                    <div class="relative mb-6 min-h-0 flex-1 overflow-hidden">
                        <Transition :name="`fpd-screen-${direction}`" mode="out-in">
                            <div :key="active" class="absolute inset-0 px-4 pt-1 pb-2">
                                <FlowPhoneScreen :name="steps[active].key" :text="text" />
                            </div>
                        </Transition>
                    </div>
                    <span class="absolute bottom-2 left-1/2 h-1 w-24 -translate-x-1/2 rounded-full bg-ink/80"></span>
                </div>
            </div>
        </div>

        <!-- The steps, sliding out of the phone: first half to its left, the rest to its right. -->
        <ol
            v-for="(side, sideIndex) in sides"
            :key="sideIndex"
            :start="side[0]?.index + 1"
            :class="['flex min-w-0 flex-col gap-3 lg:row-start-1', sideIndex === 0 ? 'lg:col-start-1' : 'lg:col-start-3']"
        >
            <li v-for="{ step, index } in side" :key="step.key">
                <button
                    type="button"
                    :class="[
                        'fpd-card flex w-full min-w-0 items-center gap-3 rounded-2xl border p-3 text-left',
                        sideIndex === 0 ? 'fpd-card--left lg:flex-row-reverse lg:text-right' : 'fpd-card--right',
                        index < shown ? 'fpd-card--out' : 'fpd-card--in',
                        index === active ? 'border-brand-300 bg-white shadow-lg shadow-brand-900/5' : 'border-transparent bg-white/60',
                    ]"
                    :style="{ '--fold-delay': `${(steps.length - 1 - index) * 70}ms` }"
                    :aria-current="index === active ? 'step' : undefined"
                    @click="choose(index)"
                >
                    <span :class="['grid size-12 shrink-0 place-items-center rounded-full transition-colors', index === active ? 'bg-brand-50' : 'bg-ivory']" aria-hidden="true">
                        <span class="block size-9" v-html="step.illustration"></span>
                    </span>
                    <span class="min-w-0 text-sm">
                        <span class="block text-[10px] font-semibold tracking-[0.2em] text-brand-600 uppercase">{{ step.number }}</span>
                        <span class="block font-display text-base font-semibold">{{ step.label }}</span>
                        <span class="text-ink-muted">{{ step.description }}</span>
                    </span>
                </button>
            </li>
        </ol>
    </div>
</template>

<style scoped>
/* One easing for everything that moves, so the phone and the cards feel like one piece. */
.fpd-card {
    transition:
        opacity 0.6s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.7s cubic-bezier(0.22, 1, 0.36, 1),
        filter 0.6s cubic-bezier(0.22, 1, 0.36, 1),
        background-color 0.4s ease,
        border-color 0.4s ease,
        box-shadow 0.4s ease;
}

/* Still inside the phone: tucked toward it, small and soft. Folding back in goes last card first. */
.fpd-card--in {
    opacity: 0;
    transform: translateY(-1.25rem) scale(0.92);
    filter: blur(4px);
    transition-delay: var(--fold-delay), var(--fold-delay), var(--fold-delay), 0s, 0s, 0s;
}

@media (min-width: 64rem) {
    .fpd-card--left.fpd-card--in {
        transform: translateX(3.5rem) scale(0.92);
    }

    .fpd-card--right.fpd-card--in {
        transform: translateX(-3.5rem) scale(0.92);
    }
}

.fpd-card--out {
    opacity: 1;
    transform: none;
    filter: none;
}

/* The phone's screen: out one way, in from the other. */
.fpd-screen-forward-enter-active,
.fpd-screen-forward-leave-active,
.fpd-screen-back-enter-active,
.fpd-screen-back-leave-active {
    transition:
        opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.fpd-screen-forward-enter-from,
.fpd-screen-back-leave-to {
    opacity: 0;
    transform: translateX(1.5rem);
}

.fpd-screen-forward-leave-to,
.fpd-screen-back-enter-from {
    opacity: 0;
    transform: translateX(-1.5rem);
}

@media (prefers-reduced-motion: reduce) {
    .fpd-card,
    .fpd-card--in {
        transition: none;
        filter: none;
    }
}
</style>
