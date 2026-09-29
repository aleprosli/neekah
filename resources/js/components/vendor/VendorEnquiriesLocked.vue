<script setup>
/**
 * Enquiries on Basic: couples can still write, so the vendor sees how many
 * are waiting and when they came in, but not who or what. Reading and
 * answering them is Neekah Pro.
 */
defineProps({
    waiting: { type: Number, required: true },
    total: { type: Number, required: true },
    recent: { type: Array, default: () => [] },
    proUrl: { type: String, required: true },
});
</script>

<template>
    <div class="mx-auto flex w-full max-w-2xl min-w-0 flex-col gap-6">
        <section class="relative overflow-hidden rounded-3xl border border-gold-300/60 bg-linear-to-br from-surface-raised to-brand-50 p-6 text-center sm:p-10">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-gold-300/40 text-2xl" aria-hidden="true">🔒</span>
            <p class="mt-4 font-display text-4xl font-semibold">{{ waiting }}</p>
            <h2 class="mt-1 font-display text-xl font-semibold">{{ $t('enquiries_locked.waiting_title', { count: waiting }) }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-ink-muted">{{ waiting ? $t('enquiries_locked.waiting_body') : $t('enquiries_locked.none_body') }}</p>
            <a :href="proUrl" class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/15 transition hover:bg-brand-700">{{ $t('enquiries_locked.upgrade') }}</a>
            <p v-if="total" class="mt-3 text-xs text-ink-muted">{{ $t('enquiries_locked.total', { count: total }) }}</p>
        </section>

        <!-- What is waiting, without the words: who and what stay behind the lock. -->
        <ul v-if="recent.length" class="flex flex-col gap-2" :aria-label="$t('enquiries_locked.recent')">
            <li v-for="enquiry in recent" :key="enquiry.id" class="flex min-w-0 items-center gap-4 rounded-2xl border border-line bg-surface-raised p-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-muted text-sm" aria-hidden="true">💬</span>
                <div class="min-w-0 flex-1">
                    <div class="h-3 w-2/5 rounded-full bg-surface-muted blur-[1px]" aria-hidden="true"></div>
                    <div class="mt-2 h-2.5 w-4/5 rounded-full bg-surface-muted/80 blur-[1px]" aria-hidden="true"></div>
                </div>
                <div class="shrink-0 text-right text-xs text-ink-muted">
                    <p>{{ enquiry.received }}</p>
                    <p v-if="enquiry.event_date">{{ $t('enquiries_locked.for_date', { date: enquiry.event_date }) }}</p>
                </div>
            </li>
        </ul>
    </div>
</template>
