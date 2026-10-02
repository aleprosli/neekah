<script setup>
/**
 * The column under the vendor's hero (x-vendor-hero, Blade, which carries the
 * rank badge): what needs doing, the first item large. Then for Pro, the
 * business and the next weddings; for Basic, what Basic can do on its own,
 * Boost explained with the real marketplace screenshot, and what Pro opens —
 * so a vendor with nothing to fix still has somewhere to go. The ranking
 * card beside it is Blade too (x-vendor-ranking).
 */
import { computed } from 'vue';
import UiBadge from '../ui/UiBadge.vue';

const props = defineProps({
    vendor: { type: Object, required: true },
    actions: { type: Array, default: () => [] },
    business: { type: Array, default: null },
    upcoming: { type: Array, default: () => [] },
    standing: { type: Object, required: true },
    locked: { type: Array, default: () => [] },
    /** What Basic does on its own: [{ key, url }]. */
    tools: { type: Array, default: () => [] },
    /** { tokens, url, screenshot } */
    boost: { type: Object, default: null },
});

const isPro = computed(() => props.vendor.plan === 'pro');


/** Line icons on a 24px grid, as the sidebar draws them (x-nav-icon). */
const icons = {
    clock: ['M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'M12 7v5l3 2'],
    receipt: ['M6 3h12v18l-3-2-3 2-3-2-3 2V3Z', 'M9 8h6M9 12h6'],
    chat: ['M4 5h16v11H9l-5 4V5Z', 'M9 10h6'],
    calendar: ['M4 6h16v14H4z', 'M4 10h16M9 3v4M15 3v4'],
    lock: ['M5 11h14v10H5z', 'M8 11V7a4 4 0 0 1 8 0v4'],
    image: ['M3 5h18v14H3z', 'M10 10a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z', 'm4 18 5-5 4 4 3-3 4 4'],
    box: ['m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z', 'm4 7.5 8 4.5 8-4.5M12 12v9'],
    store: ['M3 9.5 5 4h14l2 5.5', 'M4 9.5h16V20H4z', 'M9 20v-5h6v5'],
    rocket: ['M5 15c-1 1-1.5 3.5-1.5 5.5 2 0 4.5-.5 5.5-1.5', 'M9 15 5.5 11.5 8 9h5l5-5c1.5 0 2 .5 2 2l-5 5v5l-2.5 2.5L9 15Z'],
    document: ['M6 3h8l4 4v14H6V3Z', 'M14 3v4h4', 'M9 12h6', 'M9 16h6'],
    pencil: ['M4 20h4L20 8l-4-4L4 16v4Z', 'm14 6 4 4'],
    trophy: ['M8 4h8v5a4 4 0 0 1-8 0V4Z', 'M8 5H5v2a3 3 0 0 0 3 3', 'M16 5h3v2a3 3 0 0 1-3 3', 'M12 13v4', 'M9 20h6'],
    sparkle: ['M12 3l1.8 5.6L19.5 10l-5.7 1.4L12 17l-1.8-5.6L4.5 10l5.7-1.4L12 3Z'],
    heart: ['M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10Z'],
    wallet: ['M3 7h15a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7Z', 'M3 7a2 2 0 0 1 2-2h11', 'M17 13h.01'],
    star: ['m12 3.5 2.6 5.5 6 .8-4.3 4.2 1 6-5.3-2.9-5.3 2.9 1-6L3.4 9.8l6-.8L12 3.5Z'],
};

/** Which icon each "perlu tindakan" item and each locked feature gets. */
const actionIcon = (key) => ({
    pro_expiring: 'clock', receipts: 'receipt', enquiries: 'chat', enquiries_locked: 'lock',
    calendar: 'calendar', online: 'calendar', boost: 'rocket',
    setup_cover: 'image', setup_portfolio: 'image', setup_pakej: 'box', setup_profil: 'store',
}[key] ?? 'sparkle');
const toolIcon = (key) => ({ profile: 'store', packages: 'box', portfolio: 'image', reviews: 'star' }[key] ?? 'sparkle');
const featureIcon = (key) => ({ calendar: 'calendar', bookings: 'receipt', enquiries: 'chat', quotations: 'document', contracts: 'pencil', points: 'trophy' }[key] ?? 'sparkle');
const businessIcon = ['heart', 'clock', 'chat', 'wallet'];

const tones = {
    brand: 'bg-brand-600 text-white shadow-brand-600/30',
    amber: 'bg-amber-500 text-white shadow-amber-500/30',
    gold: 'bg-gold-400 text-brand-900 shadow-gold-500/30',
    muted: 'bg-brand-600 text-white shadow-brand-600/30',
};
</script>

<template>
    <div class="flex min-w-0 flex-col gap-6">
        <!-- What needs doing -->
        <section class="rounded-[1.75rem] border border-gold-300/60 bg-surface-raised p-5 shadow-sm shadow-brand-900/5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-xl font-semibold">{{ actions.length ? $t('vendor_home.todo') : $t('vendor_home.all_clear') }}</h2>
                <span v-if="actions.length" class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ actions.length }}</span>
            </div>

            <p v-if="!actions.length" class="mt-2 text-sm text-ink-muted">{{ isPro ? $t('vendor_home.all_clear_body') : $t('vendor_home.all_clear_basic') }}</p>

            <template v-else>
                <!-- The one to do now -->
                <a :href="actions[0].href" class="group mt-4 grid grid-cols-[auto_minmax(0,1fr)] items-start gap-x-4 gap-y-4 rounded-2xl bg-linear-to-br from-brand-50 via-surface to-gold-300/20 p-4 ring-1 ring-brand-100 transition hover:ring-brand-300 sm:flex sm:flex-row sm:items-center sm:p-5">
                    <span :class="['flex size-12 shrink-0 items-center justify-center rounded-2xl shadow-sm', tones[actions[0].tone] || tones.muted]">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[actionIcon(actions[0].key)]" :key="d" :d="d" /></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ actions[0].title }}</span>
                        <span class="mt-0.5 line-clamp-2 block text-sm text-ink-muted">{{ actions[0].body }}</span>
                    </span>
                    <span class="col-span-2 inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition group-hover:bg-brand-700 sm:w-auto">
                        {{ actions[0].cta }}
                        <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </span>
                </a>

                <!-- Then these -->
                <ul v-if="actions.length > 1" class="mt-3 grid gap-2 sm:grid-cols-2">
                    <li v-for="action in actions.slice(1)" :key="action.key">
                        <a :href="action.href" class="group flex items-center gap-3 rounded-2xl px-3 py-3 ring-1 ring-line transition hover:bg-surface-muted hover:ring-brand-200">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-ink-muted group-hover:text-brand-700">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[actionIcon(action.key)]" :key="d" :d="d" /></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ action.title }}</span>
                                <span class="block truncate text-xs text-ink-muted">{{ action.cta }}</span>
                            </span>
                            <svg class="size-4 shrink-0 text-ink-muted transition group-hover:translate-x-0.5 group-hover:text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                        </a>
                    </li>
                </ul>
            </template>
        </section>

        <!-- Pro: the business -->
        <template v-if="isPro">
            <section class="grid grid-cols-2 gap-3 sm:gap-4 2xl:grid-cols-4">
                <component :is="stat.href ? 'a' : 'div'" v-for="(stat, index) in business" :key="stat.label" :href="stat.href" class="group flex min-w-0 flex-col gap-3 rounded-3xl bg-surface-raised p-4 ring-1 ring-line transition hover:-translate-y-0.5 hover:ring-brand-200 sm:p-5">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-linear-to-br from-brand-50 to-gold-300/40 text-brand-700">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[businessIcon[index] ?? 'sparkle']" :key="d" :d="d" /></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-medium text-ink-muted">{{ stat.label }}</span>
                        <span class="mt-0.5 block font-display text-xl font-semibold break-words sm:text-2xl">{{ stat.value }}</span>
                        <span v-if="stat.hint" class="mt-0.5 block text-xs text-ink-muted">{{ stat.hint }}</span>
                    </span>
                </component>
            </section>

            <section class="flex min-w-0 flex-col gap-3">
                <h2 class="font-display text-xl font-semibold">{{ $t('vendor_home.upcoming') }}</h2>
                <p v-if="!upcoming.length" class="rounded-3xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">{{ $t('vendor_home.upcoming_empty') }}</p>
                <ul v-else class="divide-y divide-line overflow-hidden rounded-3xl bg-surface-raised ring-1 ring-line">
                    <li v-for="booking in upcoming" :key="booking.reference">
                        <a :href="booking.url" class="flex min-w-0 items-center gap-4 p-4 transition hover:bg-surface-muted/60">
                            <div class="w-12 shrink-0 rounded-2xl bg-linear-to-b from-brand-600 to-brand-800 py-1.5 text-center text-white">
                                <p class="font-display text-lg leading-none font-semibold">{{ booking.day }}</p>
                                <p class="text-[10px] font-semibold text-gold-300 uppercase">{{ booking.month }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ booking.customer }}</p>
                                <p class="truncate text-xs text-ink-muted">{{ booking.package_name }} · {{ booking.reference }}</p>
                            </div>
                            <UiBadge :label="booking.status_label" :tone="booking.status_tone" />
                        </a>
                    </li>
                </ul>
            </section>
        </template>

        <!-- Basic: what it does on its own -->
        <section v-if="!isPro && tools.length" class="flex min-w-0 flex-col gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ $t('vendor_home.tools_title') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('vendor_home.tools_body') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <a v-for="tool in tools" :key="tool.key" :href="tool.url" class="group flex min-w-0 flex-col gap-3 rounded-3xl bg-surface-raised p-4 ring-1 ring-line transition hover:-translate-y-0.5 hover:ring-brand-200 sm:p-5">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-linear-to-br from-brand-50 to-gold-300/40 text-brand-700">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[toolIcon(tool.key)]" :key="d" :d="d" /></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold">{{ $t(`vendor_home.tool_${tool.key}`) }}</span>
                        <span class="mt-0.5 block text-xs text-ink-muted">{{ $t(`vendor_home.tool_${tool.key}_body`) }}</span>
                    </span>
                </a>
            </div>
        </section>

        <!-- Boost, explained with the real marketplace -->
        <section v-if="!isPro && boost" class="relative overflow-hidden rounded-[1.75rem] bg-linear-to-br from-gold-300/40 via-surface-raised to-brand-50 p-5 ring-1 ring-gold-300/60 sm:p-6">
            <div class="grid items-center gap-6 sm:grid-cols-[minmax(0,1fr)_minmax(0,13rem)]">
                <div class="min-w-0">
                    <p class="flex items-center gap-2 text-[11px] font-semibold tracking-[0.2em] text-brand-700 uppercase">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons.rocket" :key="d" :d="d" /></svg>
                        Boost
                    </p>
                    <h2 class="mt-1 font-display text-2xl font-semibold">{{ $t('vendor_home.boost_title') }}</h2>
                    <p class="mt-2 text-sm text-ink-muted">{{ $t('vendor_home.boost_body') }}</p>
                    <ul class="mt-3 flex flex-col gap-1.5 text-sm">
                        <li v-for="point in ['boost_point_top', 'boost_point_label', 'boost_point_token']" :key="point" class="flex gap-2">
                            <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-[9px] font-bold text-white" aria-hidden="true">✓</span>
                            <span>{{ $t(`vendor_home.${point}`) }}</span>
                        </li>
                    </ul>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <a :href="boost.url" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ boost.tokens ? $t('vendor_home.boost_cta_use') : $t('vendor_home.boost_cta_get') }}</a>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-gold-300">{{ $t('vendor_home.boost_tokens', { count: boost.tokens }) }}</span>
                    </div>
                </div>

                <!-- The mockup: a phone showing the boosted card at the top of the marketplace -->
                <figure class="mx-auto w-full max-w-[13rem]">
                    <div class="rounded-[2rem] bg-ink p-2 shadow-xl shadow-brand-900/25">
                        <div class="overflow-hidden rounded-[1.6rem] bg-ivory">
                            <div class="flex items-center justify-between bg-white px-3 py-2 text-[10px] font-semibold text-ink-muted">
                                <span>{{ $t('vendor_home.boost_mock_sort') }}</span>
                                <span class="rounded-full bg-gold-300 px-1.5 py-px text-[9px] font-bold text-brand-900">{{ $t('vendor_home.boost_mock_label') }}</span>
                            </div>
                            <img :src="boost.screenshot" alt="" width="524" height="810" loading="lazy" decoding="async" class="h-auto w-full">
                        </div>
                    </div>
                    <figcaption class="mt-2 text-center text-[11px] text-ink-muted">{{ $t('vendor_home.boost_mock_caption') }}</figcaption>
                </figure>
            </div>
        </section>

        <!-- Basic: what Pro opens -->
        <section v-if="!isPro" class="relative overflow-hidden rounded-[1.75rem] bg-linear-to-br from-ink via-brand-900 to-ink p-6 text-surface ring-1 ring-gold-300/50 sm:p-7">
            <div class="pointer-events-none absolute -top-20 -right-16 size-64 rounded-full bg-gold-300/15 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold tracking-[0.3em] text-gold-300 uppercase">✦ Neekah Pro</p>
                        <h2 class="mt-1 font-display text-2xl font-semibold">{{ $t('vendor_home.pro_title') }}</h2>
                        <p class="mt-1 text-sm text-surface/75">{{ $t('vendor_home.pro_body') }}</p>
                    </div>
                    <a :href="vendor.proUrl" class="shrink-0 rounded-full bg-gold-300 px-6 py-2.5 text-center text-sm font-semibold text-ink transition hover:bg-gold-400">{{ $t('vendor_home.upgrade') }}</a>
                </div>
                <ul class="grid min-w-0 gap-2 sm:grid-cols-2">
                    <li v-for="feature in locked" :key="feature.key" class="flex min-w-0 gap-3 rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gold-300/15 text-gold-300">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[featureIcon(feature.key)]" :key="d" :d="d" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">{{ feature.label }}</p>
                            <p class="text-xs text-surface/70">{{ feature.description }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
