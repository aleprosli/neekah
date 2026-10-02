<script setup>
/**
 * The couple's home, kept to what moves them forward: the next step (one,
 * large) and the two after it, four shortcuts that each say where they
 * stand, and the vendors they have booked. The countdown sits above this
 * and the partner card between the steps and the shortcuts, both rendered
 * by Blade, so the page mounts this twice: `part` "steps", then "rest".
 *
 * The wall of stat cards, the budget bar and the twelve-category checklist
 * it used to carry are gone: couples were not coming back to a page that
 * asked them to read it.
 */
import UiBadge from '../ui/UiBadge.vue';
import UiCategoryTile from '../ui/UiCategoryTile.vue';

defineProps({
    part: { type: String, default: 'steps' },
    hasWedding: { type: Boolean, required: true },
    createUrl: { type: String, required: true },
    steps: { type: Array, default: () => [] },
    stepsDone: { type: Number, default: 0 },
    stepsTotal: { type: Number, default: 0 },
    shortcuts: { type: Array, default: () => [] },
    bookings: { type: Array, default: () => [] },
    findVendorsUrl: { type: String, default: null },
    convertUrl: { type: String, default: null },
});

/** Line icons on a 24px grid, as the sidebar draws them (x-nav-icon). */
const icons = {
    mail: ['M3 6h18v12H3z', 'm3 7 9 6 9-6'],
    rings: ['M14.5 14a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0Z', 'M21.5 14a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0Z', 'm9 5 1.5 2.5h-3L9 5Z'],
    users: ['M12.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z', 'M2.5 20a6.5 6.5 0 0 1 13 0', 'M16 5.5a3.5 3.5 0 0 1 0 7', 'M17.5 14.5A6.5 6.5 0 0 1 21.5 20'],
    check: ['M4 5h16v15H4z', 'm8 12 3 3 5-6'],
    search: ['M18 11a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z', 'm20 20-3.5-3.5'],
    wallet: ['M3 7h15a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7Z', 'M3 7a2 2 0 0 1 2-2h11', 'M17 13h.01'],
};

const tones = {
    card: 'from-brand-50 to-gold-300/40 text-brand-700',
    guests: 'from-sky-50 to-brand-50 text-sky-800',
    checklist: 'from-emerald-50 to-gold-300/30 text-emerald-800',
    budget: 'from-gold-300/40 to-brand-50 text-gold-600',
};
</script>

<template>
    <!-- No wedding yet: one card, one button. -->
    <section v-if="!hasWedding && part === 'steps'" class="relative overflow-hidden rounded-[1.75rem] bg-linear-to-br from-brand-700 via-brand-800 to-brand-900 px-6 py-12 text-center text-white shadow-lg shadow-brand-900/20 sm:px-10">
        <div class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-gold-300/20 blur-3xl" aria-hidden="true"></div>
        <p class="relative font-script text-3xl text-gold-300 sm:text-4xl">{{ $t('countdown.bila_hari_bahagia') }}</p>
        <h2 class="relative mt-2 font-display text-3xl font-semibold sm:text-4xl">{{ $t('customer.start_project_title') }}</h2>
        <p class="relative mx-auto mt-3 max-w-md text-sm text-white/80">{{ $t('customer.tetapkan_tarikh_lokasi_dan_bajet') }}</p>
        <a :href="createUrl" class="relative mt-6 inline-flex rounded-full bg-white px-6 py-3 text-sm font-semibold text-brand-800 shadow-sm transition hover:bg-gold-300">{{ $t('customer.start_project_action') }}</a>
        <p v-if="convertUrl" class="relative mt-6 text-xs text-white/75">
            {{ $t('dashboard_steps.vendor_question') }}
            <a :href="convertUrl" class="font-semibold text-white underline underline-offset-4">{{ $t('dashboard_steps.vendor_switch') }}</a>
        </p>
    </section>

    <!-- Next steps -->
    <section v-else-if="part === 'steps'" class="rounded-[1.75rem] border border-gold-300/60 bg-surface-raised p-5 shadow-sm shadow-brand-900/5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-xl font-semibold">{{ steps.length ? $t('dashboard_steps.heading') : $t('dashboard_steps.all_done') }}</h2>
                <span class="flex shrink-0 items-center gap-2 text-xs text-ink-muted">
                    <span class="flex gap-1" aria-hidden="true">
                        <span v-for="n in stepsTotal" :key="n" :class="['size-1.5 rounded-full', n <= stepsDone ? 'bg-emerald-500' : 'bg-line']"></span>
                    </span>
                    {{ $t('dashboard_steps.progress', { done: stepsDone, total: stepsTotal }) }}
                </span>
            </div>

            <p v-if="!steps.length" class="mt-2 text-sm text-ink-muted">{{ $t('dashboard_steps.all_done_body') }}</p>

            <template v-else>
                <!-- The one to do now -->
                <a :href="steps[0].url" class="group mt-4 flex flex-col gap-4 rounded-2xl bg-linear-to-br from-brand-50 via-surface to-gold-300/20 p-4 ring-1 ring-brand-100 transition hover:ring-brand-300 sm:flex-row sm:items-center sm:p-5">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm shadow-brand-600/30">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[steps[0].icon]" :key="d" :d="d" /></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] font-semibold tracking-wide text-brand-700 uppercase">{{ $t('dashboard_steps.now') }}</span>
                        <span class="mt-0.5 block font-semibold">{{ steps[0].title }}</span>
                        <span class="mt-0.5 block text-sm text-ink-muted">{{ steps[0].hint }}</span>
                    </span>
                    <span class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition group-hover:bg-brand-700 sm:w-auto">
                        {{ steps[0].action }}
                        <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </span>
                </a>

                <!-- Then these -->
                <ul v-if="steps.length > 1" class="mt-3 grid gap-2 sm:grid-cols-2">
                    <li v-for="step in steps.slice(1)" :key="step.key">
                        <a :href="step.url" class="group flex items-center gap-3 rounded-2xl px-3 py-3 ring-1 ring-line transition hover:bg-surface-muted hover:ring-brand-200">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-ink-muted group-hover:text-brand-700">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[step.icon]" :key="d" :d="d" /></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ step.title }}</span>
                                <span class="block truncate text-xs text-ink-muted">{{ step.action }}</span>
                            </span>
                            <svg class="size-4 shrink-0 text-ink-muted transition group-hover:translate-x-0.5 group-hover:text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                        </a>
                    </li>
                </ul>
            </template>
        </section>

    <div v-else-if="hasWedding" class="flex flex-col gap-8">
        <!-- Shortcuts -->
        <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <a v-for="shortcut in shortcuts" :key="shortcut.key" :href="shortcut.url" class="group flex min-w-0 flex-col gap-3 rounded-3xl bg-surface-raised p-4 ring-1 ring-line transition hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-20px_rgb(0_0_0/0.35)] hover:ring-brand-200 sm:p-5">
                <span :class="['flex size-11 items-center justify-center rounded-2xl bg-linear-to-br', tones[shortcut.key]]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="d in icons[shortcut.icon]" :key="d" :d="d" /></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-ink-muted">{{ shortcut.title }}</span>
                    <span class="mt-0.5 block truncate font-display text-xl font-semibold sm:text-2xl">{{ shortcut.value }}</span>
                    <span class="mt-0.5 block text-xs break-words text-ink-muted">{{ shortcut.hint }}</span>
                </span>
            </a>
        </section>

        <!-- Booked vendors, once there are any -->
        <section v-if="bookings.length" class="flex min-w-0 flex-col gap-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-xl font-semibold">{{ $t('customer.your_vendors') }}</h2>
                <a v-if="findVendorsUrl" :href="findVendorsUrl" class="text-sm font-medium text-brand-700 underline-offset-4 hover:underline">{{ $t('common.find_vendors') }}</a>
            </div>
            <ul class="divide-y divide-line overflow-hidden rounded-3xl bg-surface-raised ring-1 ring-line">
                <li v-for="booking in bookings" :key="booking.reference">
                    <a :href="booking.url" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                        <UiCategoryTile v-bind="booking.category" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ booking.vendor }}</p>
                            <p class="truncate text-sm text-ink-muted">{{ booking.summary }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold">{{ booking.total }}</p>
                            <UiBadge :label="booking.status_label" :tone="booking.status_tone" />
                        </div>
                    </a>
                </li>
            </ul>
        </section>

        <p v-if="convertUrl" class="text-center text-xs text-ink-muted">
            {{ $t('dashboard_steps.vendor_question') }}
            <a :href="convertUrl" class="font-medium text-brand-700 underline underline-offset-4">{{ $t('dashboard_steps.vendor_switch') }}</a>
        </p>
    </div>
</template>
