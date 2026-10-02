<script setup>
/**
 * The top of the Neekah Pro page, told as a before and after.
 *
 * Before: the vendor's day as scattered notes — enquiries lost in WhatsApp,
 * a PDF quotation, a paper diary — tangled together by dashed lines that
 * never stop moving. After: each of those notes, crossed out, above the Pro
 * feature that replaces it, its icon drawing itself in as it scrolls into
 * view.
 *
 * Pro Elite closes the after section: the same promise, for the vendors who
 * climb to Top or Elite, so the page tells one story instead of tacking it on
 * below the prices.
 *
 * Every animation is motion-safe (keyframes in resources/css/app.css), and
 * the features are shown at once when IntersectionObserver is missing.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    eyebrow: { type: String, required: true },
    headline: { type: String, required: true },
    lead: { type: String, required: true },
    footnote: { type: String, required: true },
    afterHeading: { type: String, required: true },
    afterLead: { type: String, required: true },
    /** { url, title, body, label }: the last tile of the grid; url and label are null for a Pro vendor. */
    cta: { type: Object, default: null },
    /** [{ key, title, body, pain }] in the order the notes are scattered. */
    benefits: { type: Array, required: true },
    /** { kicker, title, body, earned, isElite, status, perks: [{ key, title, body }] }, null when Elite is off. */
    elite: { type: Object, default: null },
});

/**
 * Where each note sits, as % of the stage, and its tilt. Desktop is a wide
 * stage (1000 × 460) with a row above and a row below; a phone gets a tall
 * one (400 × 680) that zigzags down.
 */
const stages = {
    wide: {
        width: 1000,
        height: 460,
        spots: [[11, 27, -4], [37, 16, 2], [63, 22, -2], [88, 29, 3], [24, 79, 3], [51, 73, -3], [77, 82, 2]],
        links: [[0, 4, true], [1, 4, false], [1, 5, false], [2, 5, true], [2, 6, false], [3, 6, true]],
    },
    tall: {
        width: 400,
        height: 680,
        spots: [[30, 7, -4], [70, 21, 3], [30, 35, -2], [70, 49, 3], [30, 63, 2], [70, 77, -3], [32, 92, 2]],
        links: [[0, 1, false], [1, 2, true], [2, 3, false], [3, 4, false], [4, 5, true], [5, 6, false]],
    },
};

/** A drifting dashed line from one note to another, sometimes with a loop in it. */
const path = (stage, [from, to, loop]) => {
    const point = (index) => [(stage.spots[index][0] * stage.width) / 100, (stage.spots[index][1] * stage.height) / 100];
    const [ax, ay] = point(from);
    const [bx, by] = point(to);
    const down = by > ay ? 1 : -1;

    if (!loop) {
        return `M${ax} ${ay} C${ax + 40} ${ay + 110 * down}, ${bx - 50} ${by - 120 * down}, ${bx} ${by}`;
    }

    const mx = (ax + bx) / 2;
    const my = (ay + by) / 2;

    return `M${ax} ${ay} C${ax + 50} ${ay + 70 * down}, ${mx + 70} ${my - 40}, ${mx} ${my}`
        + ` C${mx - 45} ${my + 45}, ${mx - 70} ${my - 35}, ${mx - 5} ${my - 25}`
        + ` C${mx + 60} ${my - 10}, ${bx - 30} ${by - 90 * down}, ${bx} ${by}`;
};

/** Line icons on a 24px grid, stroked, as the sidebar draws them (x-nav-icon). */
const icons = {
    enquiries: ['M4 5h16v11H9l-5 4V5Z', 'M9 10h6'],
    quotations: ['M6 3h8l4 4v14H6V3Z', 'M14 3v4h4', 'M9 12h6', 'M9 16h6'],
    contracts: ['M4 20h4L20 8l-4-4L4 16v4Z', 'm14 6 4 4', 'M13 20h7'],
    booking: ['M4 6h16v14H4z', 'M4 10h16', 'M9 3v4', 'M15 3v4', 'm9 15 2 2 4-4'],
    boost: ['M5 15c-1 1-1.5 3.5-1.5 5.5 2 0 4.5-.5 5.5-1.5', 'M9 15 5.5 11.5 8 9h5l5-5c1.5 0 2 .5 2 2l-5 5v5l-2.5 2.5L9 15Z'],
    ranking: ['M8 4h8v5a4 4 0 0 1-8 0V4Z', 'M8 5H5v2a3 3 0 0 0 3 3', 'M16 5h3v2a3 3 0 0 1-3 3', 'M12 13v4', 'M9 20h6'],
    badge: ['m3 8 4.5 4L12 5l4.5 7L21 8l-2 11H5L3 8Z'],
};

/** Pro Elite's perks, drawn the same way in gold. */
const perkIcons = {
    badge: ['M12 3 4 6v6c0 4.5 3.4 8 8 9 4.6-1 8-4.5 8-9V6l-8-3Z', 'm9 12 2 2 4-4'],
    row: ['M4 5h16v5H4z', 'M4 14h7v5H4z', 'M13 14h7v5h-7z'],
    order: ['M7 20V5', 'm3 9 4-4 4 4', 'M14 7h7', 'M14 12h5', 'M14 17h3'],
    tokens: ['M12 3v2', 'M12 19v2', 'M5 12H3', 'M21 12h-2', 'm6.3 6.3-1.4-1.4', 'm19.1 19.1-1.4-1.4', 'm6.3 17.7-1.4 1.4', 'm19.1 4.9-1.4 1.4', 'M12 8l1.2 2.8L16 12l-2.8 1.2L12 16l-1.2-2.8L8 12l2.8-1.2Z'],
};

const shown = ref(false);
const list = ref(null);
let observer = null;

onMounted(() => {
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        shown.value = true;
        return;
    }

    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            shown.value = true;
            observer.disconnect();
        }
    }, { threshold: 0.15 });
    observer.observe(list.value);
});

onBeforeUnmount(() => observer?.disconnect());

const tone = ['from-brand-50 to-gold-300/40', 'from-gold-300/30 to-brand-50', 'from-emerald-50 to-gold-300/30', 'from-sky-50 to-brand-50'];
</script>

<template>
    <div class="flex flex-col gap-10">
        <!-- Before -->
        <section class="relative overflow-hidden rounded-[2rem] bg-[#f3eddf] px-5 pt-12 pb-10 sm:px-10 sm:pt-16">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-[11px] font-semibold tracking-[0.3em] text-brand-700 uppercase">{{ eyebrow }}</p>
                <h2 class="mt-3 font-display text-4xl leading-[1.05] font-semibold tracking-tight text-ink sm:text-5xl lg:text-6xl">{{ headline }}</h2>
                <p class="mx-auto mt-5 max-w-2xl text-base text-ink-muted sm:text-lg">{{ lead }}</p>
            </div>

            <div v-for="(stage, name) in stages" :key="name" :class="['relative mx-auto mt-10', name === 'wide' ? 'hidden max-w-5xl md:block' : 'max-w-sm md:hidden']" :style="{ aspectRatio: `${stage.width} / ${stage.height}` }" aria-hidden="true">
                <svg class="absolute inset-0 size-full text-[#b0683a]" :viewBox="`0 0 ${stage.width} ${stage.height}`" fill="none">
                    <path
                        v-for="(link, index) in stage.links"
                        :key="index"
                        :d="path(stage, link)"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-dasharray="7 7"
                        stroke-linecap="round"
                        vector-effect="non-scaling-stroke"
                        class="motion-safe:animate-[nk-march_1.6s_linear_infinite]"
                    />
                </svg>

                <span
                    v-for="(benefit, index) in benefits.slice(0, stage.spots.length)"
                    :key="benefit.key"
                    class="absolute rounded-2xl border border-ink/5 bg-[#fcfaf4] px-4 py-2.5 text-sm font-bold whitespace-nowrap text-ink shadow-[0_14px_28px_-14px_rgb(0_0_0/0.4)] motion-safe:animate-[nk-drift_7s_ease-in-out_infinite] sm:px-5 sm:py-3 md:text-lg"
                    :style="{
                        left: `${stage.spots[index][0]}%`,
                        top: `${stage.spots[index][1]}%`,
                        '--tilt': `${stage.spots[index][2]}deg`,
                        transform: 'translate(-50%, -50%) rotate(var(--tilt))',
                        animationDelay: `${index * -0.9}s`,
                    }"
                >{{ benefit.pain }}</span>
            </div>

            <p class="mx-auto mt-8 max-w-xl text-center text-sm text-ink-muted sm:text-base">{{ footnote }}</p>
        </section>

        <!-- After -->
        <section class="flex flex-col gap-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ afterHeading }}</h2>
                <p class="mt-3 text-ink-muted">{{ afterLead }}</p>
            </div>

            <ul ref="list" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <li
                    v-for="(benefit, index) in benefits"
                    :key="benefit.key"
                    :class="['group flex flex-col gap-3 rounded-3xl bg-surface-raised p-5 ring-1 ring-line transition duration-700 ease-out hover:-translate-y-1 hover:shadow-[0_18px_40px_-24px_rgb(0_0_0/0.35)] hover:ring-brand-300', shown ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                    :style="{ transitionDelay: shown ? `${index * 90}ms` : '0ms' }"
                >
                    <span :class="['flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br text-brand-700 motion-safe:group-hover:animate-[nk-bob_0.9s_ease-in-out]', tone[index % tone.length]]">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path
                                v-for="d in icons[benefit.key] ?? icons.badge"
                                :key="d"
                                :d="d"
                                pathLength="100"
                                stroke-dasharray="100"
                                :style="{ strokeDashoffset: shown ? 0 : 100, transition: 'stroke-dashoffset 1.4s ease', transitionDelay: `${index * 90 + 250}ms` }"
                            />
                        </svg>
                    </span>
                    <span class="w-fit rounded-full bg-[#f3eddf] px-2.5 py-0.5 text-[11px] font-medium text-ink-muted line-through decoration-brand-400/80">{{ benefit.pain }}</span>
                    <h3 class="font-semibold">{{ benefit.title }}</h3>
                    <p class="text-sm leading-relaxed text-ink-muted">{{ benefit.body }}</p>
                </li>
                <li
                    v-if="cta"
                    data-pro-cta
                    :class="['flex flex-col justify-between gap-4 rounded-3xl bg-linear-to-br from-brand-600 to-brand-800 p-5 text-white shadow-[0_18px_40px_-24px_rgb(0_0_0/0.45)] transition duration-700 ease-out', shown ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                    :style="{ transitionDelay: shown ? `${benefits.length * 90}ms` : '0ms' }"
                >
                    <div>
                        <p class="font-script text-2xl leading-none text-gold-300">Neekah Pro</p>
                        <h3 class="mt-3 font-display text-2xl leading-tight font-semibold">{{ cta.title }}</h3>
                        <p class="mt-2 text-sm text-white/80">{{ cta.body }}</p>
                    </div>
                    <a v-if="cta.url" :href="cta.url" class="w-fit rounded-full bg-gold-300 px-5 py-2.5 text-sm font-semibold text-ink transition hover:bg-gold-400">{{ cta.label }} →</a>
                </li>
            </ul>

            <!-- Pro Elite: the top of the same promise -->
            <div
                v-if="elite"
                id="elite"
                data-pro-elite
                :class="['relative mt-2 scroll-mt-24 overflow-hidden rounded-[2rem] bg-linear-to-br from-ink via-brand-900 to-ink p-6 text-surface ring-1 ring-gold-300/60 transition duration-700 ease-out sm:p-8 lg:p-10', shown ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']"
                :style="{ transitionDelay: shown ? `${(benefits.length + 1) * 90}ms` : '0ms' }"
            >
                <span class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-gold-300/15 blur-3xl" aria-hidden="true"></span>
                <span class="pointer-events-none absolute -bottom-24 -left-10 size-60 rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true"></span>

                <div class="relative grid gap-8 lg:grid-cols-12 lg:items-center">
                    <div class="flex flex-col gap-4 lg:col-span-5">
                        <div class="flex items-center gap-3">
                            <span class="relative flex size-14 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-gold-300 to-gold-500 text-ink shadow-lg shadow-gold-500/30 motion-safe:animate-[nk-bob_3s_ease-in-out_infinite]" aria-hidden="true">
                                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m3 8 4.5 4L12 5l4.5 7L21 8l-2 11H5L3 8Z" />
                                </svg>
                                <span class="absolute -top-1.5 -right-1.5 text-sm text-gold-300 motion-safe:animate-pulse">✦</span>
                            </span>
                            <div class="min-w-0">
                                <p class="font-script text-2xl leading-none text-gold-300">{{ elite.kicker }}</p>
                                <p class="mt-1 text-[11px] font-semibold tracking-[0.3em] text-surface/60 uppercase">Pro Elite</p>
                            </div>
                        </div>

                        <h3 class="font-display text-3xl leading-tight font-semibold sm:text-4xl">{{ elite.title }}</h3>
                        <p class="text-sm leading-relaxed text-surface/75 sm:text-base">{{ elite.body }}</p>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-gold-300 px-3 py-1 text-xs font-bold text-ink">{{ elite.earned }}</span>
                        </div>

                        <p v-if="elite.status" data-elite-status class="rounded-2xl bg-white/5 px-4 py-3 text-xs leading-relaxed text-surface/80 ring-1 ring-white/10">{{ elite.status }}</p>
                    </div>

                    <ul class="grid gap-3 sm:grid-cols-2 lg:col-span-7">
                        <li
                            v-for="(perk, index) in elite.perks"
                            :key="perk.key"
                            class="group flex gap-3 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 transition hover:bg-white/10 hover:ring-gold-300/40"
                        >
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gold-300/15 text-gold-300 motion-safe:group-hover:animate-[nk-bob_0.9s_ease-in-out]">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path
                                        v-for="d in perkIcons[perk.key] ?? perkIcons.badge"
                                        :key="d"
                                        :d="d"
                                        pathLength="100"
                                        stroke-dasharray="100"
                                        :style="{ strokeDashoffset: shown ? 0 : 100, transition: 'stroke-dashoffset 1.4s ease', transitionDelay: `${(benefits.length + index) * 90 + 250}ms` }"
                                    />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gold-300">{{ perk.title }}</span>
                                <span class="mt-1 block text-xs leading-relaxed text-surface/75">{{ perk.body }}</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

        </section>
    </div>
</template>
