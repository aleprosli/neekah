<script setup>
/** One question and, when it comes, the vendor's answer. */
defineProps({
    enquiry: { type: Object, required: true },
});
</script>

<template>
    <div class="flex max-w-3xl flex-col gap-4">
        <div class="rounded-2xl border border-line p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('enquiry.mesej_anda') }}</p>
            <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ enquiry.message }}</p>
            <p v-if="enquiry.context" class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">{{ enquiry.context }}</p>
        </div>

        <template v-if="enquiry.reply">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-xs font-semibold tracking-wide text-emerald-800 uppercase">Balasan {{ enquiry.vendor }} · {{ enquiry.replied_at }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ enquiry.reply }}</p>
            </div>

            <a :href="enquiry.book_url" class="w-fit rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('enquiry.tempah_vendor_ini') }}</a>
        </template>

        <p v-else class="rounded-2xl border border-dashed border-line p-5 text-sm text-ink-muted">{{ $t('enquiry.menunggu_balasan_vendor_kebanyakan_vendor') }}</p>
    </div>
</template>
