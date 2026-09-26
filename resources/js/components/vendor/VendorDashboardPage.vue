<script setup>
/**
 * The vendor's opening screen, built around what needs doing today.
 *
 * A greeting with the plan, then "Perlu tindakan" (only what applies: a
 * receipt to check, enquiries to answer, a stale calendar, a missing piece of
 * the profile, unused boost tokens), then the week's reach. Pro vendors see
 * their business numbers and the next weddings; Basic vendors see what Pro
 * would open. Standing (tier, score, rating, popularity) sits on the side.
 */
import { computed } from 'vue';
import UiBadge from '../ui/UiBadge.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    vendor: { type: Object, required: true },
    actions: { type: Array, default: () => [] },
    reach: { type: Array, required: true },
    reachUrl: { type: String, required: true },
    business: { type: Array, default: null },
    upcoming: { type: Array, default: () => [] },
    standing: { type: Object, required: true },
    locked: { type: Array, default: () => [] },
});

const isPro = computed(() => props.vendor.plan === 'pro');

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'dashboard_greeting.morning';
    if (hour < 15) return 'dashboard_greeting.afternoon';
    if (hour < 19) return 'dashboard_greeting.evening';
    return 'dashboard_greeting.night';
});

const tones = {
    brand: 'bg-brand-50 text-brand-700',
    amber: 'bg-amber-50 text-amber-800',
    gold: 'bg-gold-300/30 text-brand-900',
    muted: 'bg-surface-muted text-ink',
};
</script>

<template>
    <div class="flex min-w-0 flex-col gap-8">
        <!-- Greeting -->
        <section class="relative overflow-hidden rounded-3xl border border-gold-300/50 bg-linear-to-br from-surface-raised via-surface-raised to-brand-50 p-6 sm:p-8">
            <svg class="pointer-events-none absolute -top-16 -right-16 size-64 text-gold-400/20" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                <circle cx="80" cy="100" r="60" />
                <circle cx="120" cy="100" r="60" />
            </svg>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm text-ink-muted">{{ $t(greeting, { name: vendor.firstName }) }}</p>
                    <h1 class="mt-1 font-display text-2xl font-semibold break-words sm:text-3xl">{{ vendor.name }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span :class="['rounded-full px-2.5 py-1 font-semibold', isPro ? 'bg-gold-300 text-brand-900' : 'bg-surface-muted text-ink-muted']">
                            {{ isPro ? $t('vendor_home.plan_pro', { date: vendor.proUntil }) : $t('vendor_home.plan_basic') }}
                        </span>
                        <span class="rounded-full bg-surface-muted px-2.5 py-1 font-medium text-ink-muted">{{ vendor.tier }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a :href="vendor.publicUrl" target="_blank" rel="noopener" class="rounded-full border border-line bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('vendor_home.public_page') }}</a>
                    <a v-if="vendor.recordBookingUrl" :href="vendor.recordBookingUrl" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ {{ $t('vendor_home.record_booking') }}</a>
                    <a v-else :href="vendor.proUrl" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('vendor_home.upgrade') }}</a>
                </div>
            </div>
        </section>

        <div class="grid min-w-0 gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="flex min-w-0 flex-col gap-8">
                <!-- What needs doing -->
                <section class="flex min-w-0 flex-col gap-3">
                    <h2 class="font-display text-xl font-semibold">{{ $t('vendor_home.todo') }}</h2>
                    <div v-if="!actions.length" class="flex items-center gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5">
                        <span class="text-2xl" aria-hidden="true">🎉</span>
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $t('vendor_home.all_clear') }}</p>
                            <p class="text-sm text-ink-muted">{{ $t('vendor_home.all_clear_body') }}</p>
                        </div>
                    </div>
                    <ul v-else class="flex flex-col gap-2">
                        <li v-for="action in actions" :key="action.key">
                            <a :href="action.href" class="group flex min-w-0 items-center gap-4 rounded-2xl border border-line bg-surface-raised p-4 transition hover:border-brand-300 hover:shadow-sm">
                                <span :class="['flex size-11 shrink-0 items-center justify-center rounded-xl text-xl', tones[action.tone] || tones.muted]" aria-hidden="true">{{ action.icon }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">{{ action.title }}</p>
                                    <p class="line-clamp-2 text-sm text-ink-muted">{{ action.body }}</p>
                                </div>
                                <span class="hidden shrink-0 text-sm font-medium text-brand-700 group-hover:underline sm:block">{{ action.cta }} →</span>
                                <span class="shrink-0 text-xl text-brand-600 sm:hidden" aria-hidden="true">›</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <!-- Reach this week -->
                <section class="flex min-w-0 flex-col gap-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="font-display text-xl font-semibold">{{ $t('vendor_home.reach') }}</h2>
                        <a :href="reachUrl" class="text-sm font-medium text-brand-700 hover:underline">{{ isPro ? $t('vendor_home.see_trend') : $t('vendor_home.see_trend_pro') }}</a>
                    </div>
                    <div class="grid min-w-0 grid-cols-2 gap-3 lg:grid-cols-4">
                        <component
                            :is="item.href ? 'a' : 'div'"
                            v-for="item in reach"
                            :key="item.label"
                            :href="item.href"
                            :class="['flex min-w-0 flex-col gap-1 rounded-2xl border border-line bg-surface-raised p-4', item.href ? 'transition hover:border-brand-300' : '']"
                        >
                            <span class="text-lg" aria-hidden="true">{{ item.icon }}</span>
                            <span class="font-display text-2xl font-semibold">{{ item.value }}</span>
                            <span class="truncate text-xs text-ink-muted">{{ item.label }}</span>
                        </component>
                    </div>
                </section>

                <!-- Pro: the business -->
                <template v-if="isPro">
                    <div class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <UiStatCard v-for="stat in business" :key="stat.label" v-bind="stat" />
                    </div>

                    <section class="flex min-w-0 flex-col gap-3">
                        <h2 class="font-display text-xl font-semibold">{{ $t('vendor_home.upcoming') }}</h2>
                        <p v-if="!upcoming.length" class="rounded-2xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">{{ $t('vendor_home.upcoming_empty') }}</p>
                        <ul v-else class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface-raised">
                            <li v-for="booking in upcoming" :key="booking.reference">
                                <a :href="booking.url" class="flex min-w-0 items-center gap-4 p-4 transition hover:bg-surface-muted/60">
                                    <div class="w-12 shrink-0 rounded-xl bg-brand-50 py-1.5 text-center">
                                        <p class="font-display text-lg leading-none font-semibold text-brand-700">{{ booking.day }}</p>
                                        <p class="text-[10px] font-semibold text-brand-600 uppercase">{{ booking.month }}</p>
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

                <!-- Basic: what Pro opens -->
                <section v-else class="flex min-w-0 flex-col gap-4 rounded-3xl border border-gold-300/60 bg-gold-300/10 p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-display text-xl font-semibold">{{ $t('vendor_home.pro_title') }}</h2>
                            <p class="text-sm text-ink-muted">{{ $t('vendor_home.pro_body') }}</p>
                        </div>
                        <a :href="vendor.proUrl" class="shrink-0 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('vendor_home.upgrade') }}</a>
                    </div>
                    <ul class="grid min-w-0 gap-3 sm:grid-cols-2">
                        <li v-for="feature in locked" :key="feature.label" class="flex min-w-0 gap-3 rounded-2xl bg-surface-raised p-4">
                            <span class="text-base" aria-hidden="true">🔒</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ feature.label }}</p>
                                <p class="text-xs text-ink-muted">{{ feature.description }}</p>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Standing -->
            <aside class="flex min-w-0 flex-col gap-4 xl:sticky xl:top-24 xl:self-start">
                <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                    <h2 class="font-semibold">{{ $t('vendor_home.standing') }}</h2>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ $t('vendor_home.tier') }}</dt>
                            <dd class="font-semibold">{{ standing.tier }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ $t('vendor_home.score') }}</dt>
                            <dd class="font-semibold">{{ standing.score }} <span class="text-xs font-normal text-ink-muted">/ 100</span></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ $t('vendor_home.rating') }}</dt>
                            <dd class="font-semibold">{{ standing.rating ? `★ ${standing.rating}` : '—' }} <span class="text-xs font-normal text-ink-muted">({{ standing.reviews }})</span></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ $t('vendor_home.views30') }}</dt>
                            <dd class="font-semibold">{{ standing.views30 }}</dd>
                        </div>
                    </dl>
                    <p v-if="standing.trending" class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-medium text-brand-800">🔥 {{ $t('vendor_home.trending') }}</p>
                    <p v-if="standing.boostedUntil" class="rounded-xl bg-gold-300/30 px-3 py-2 text-xs font-medium text-brand-900">🚀 {{ $t('vendor_home.boosted_until', { date: standing.boostedUntil }) }}</p>
                    <a v-if="standing.pointsUrl" :href="standing.pointsUrl" class="text-sm font-medium text-brand-700 hover:underline">{{ $t('vendor_home.see_ranking') }} →</a>
                </section>
            </aside>
        </div>
    </div>
</template>
