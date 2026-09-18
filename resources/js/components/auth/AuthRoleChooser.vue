<script setup>
/**
 * The first question on the sign-in and sign-up pages: pengantin or vendor.
 * Each card is a plain link to its own form, so the choice is a URL — the
 * back button undoes it, and it works before any JavaScript has loaded.
 */
defineProps({
    /** [{ label, description, icon (trusted SVG from nav-icon), url }] */
    options: { type: Array, required: true },
    /** { prefix, label, url } shown under the cards. */
    footer: { type: Object, default: null },
});
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="grid gap-3 sm:grid-cols-2">
            <a
                v-for="option in options"
                :key="option.url"
                :href="option.url"
                class="group flex min-w-0 flex-col items-center gap-3 rounded-2xl border border-line bg-surface-raised px-5 py-8 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5 focus-visible:border-brand-400 focus-visible:outline-none"
            >
                <span
                    class="flex size-12 items-center justify-center rounded-xl bg-surface-muted text-ink-muted transition group-hover:bg-brand-50 group-hover:text-brand-600 [&_svg]:size-6"
                    aria-hidden="true"
                    v-html="option.icon"
                ></span>
                <span class="font-semibold">{{ option.label }}</span>
                <span class="text-sm text-ink-muted">{{ option.description }}</span>
            </a>
        </div>

        <p v-if="footer" class="text-center text-sm text-ink-muted">
            {{ footer.prefix }}
            <a :href="footer.url" class="font-semibold text-ink transition hover:text-brand-700">{{ footer.label }} →</a>
        </p>
    </div>
</template>
