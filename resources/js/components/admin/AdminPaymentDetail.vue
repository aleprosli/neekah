<script setup>
/**
 * One payment in the ledger, for the admin: what it was for and where it
 * stands, every exchange with its gateway as it happened (the link we asked
 * for, each callback and return with its raw payload, each requery), and the
 * three ways to put it right: ask the gateway again, tie it to the invoice
 * the gateway's dashboard shows, or settle it by hand.
 */
import { ref } from 'vue';

const props = defineProps({
    payment: { type: Object, required: true },
    events: { type: Array, default: () => [] },
    actions: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const tones = {
    emerald: 'bg-emerald-100 text-emerald-800',
    amber: 'bg-amber-100 text-amber-800',
    sky: 'bg-sky-100 text-sky-800',
    brand: 'bg-brand-50 text-brand-700',
    red: 'bg-red-100 text-red-800',
    muted: 'bg-surface-muted text-ink-muted',
};

const confirmPaid = ref(false);
const pretty = (value) => JSON.stringify(value, null, 2);
const dot = (event) => {
    if (event.verified === false) return 'bg-red-500';
    if (event.outcome === 'paid') return 'bg-emerald-500';
    if (['failed', 'amount_mismatch'].includes(event.outcome)) return 'bg-amber-500';
    return 'bg-ink-muted';
};
const detailEntries = Object.entries(props.payment.details ?? {});
</script>

<template>
    <div class="grid min-w-0 gap-6 break-words lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="flex min-w-0 flex-col gap-6">
            <!-- Where it stands. -->
            <section class="flex min-w-0 flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
                <div class="flex min-w-0 flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-display text-3xl font-semibold">{{ payment.amount }}</p>
                        <p class="text-sm text-ink-muted">{{ payment.subject }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', tones[payment.purpose_tone] ?? tones.muted]">{{ payment.purpose }}</span>
                        <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', tones[payment.status_tone] ?? tones.muted]">{{ payment.status }}</span>
                    </div>
                </div>

                <dl class="grid min-w-0 gap-x-6 gap-y-3 text-sm sm:grid-cols-2 [&>div]:min-w-0">
                    <div><dt class="text-ink-muted">{{ $t('payments.payer') }}</dt><dd class="font-medium"><a v-if="payment.payer" :href="payment.payer.url" class="hover:text-brand-700">{{ payment.payer.name }}</a><span v-else>—</span></dd><dd v-if="payment.payer" class="text-xs text-ink-muted">{{ payment.payer.email }}</dd></div>
                    <div><dt class="text-ink-muted">{{ $t('payments.merchant') }}</dt><dd class="font-medium">{{ payment.merchant }}</dd></div>
                    <div><dt class="text-ink-muted">{{ $t('payments.gateway') }}</dt><dd class="font-medium">{{ payment.gateway }}<template v-if="payment.method"> · {{ payment.method }}</template></dd></div>
                    <div><dt class="text-ink-muted">{{ $t('payments.created') }}</dt><dd class="font-medium">{{ payment.created_at }}</dd></div>
                    <div v-if="payment.paid_at"><dt class="text-ink-muted">{{ $t('payments.paid') }}</dt><dd class="font-medium">{{ payment.paid_at }}</dd></div>
                    <div v-if="payment.expires_at"><dt class="text-ink-muted">{{ $t('payments.expires') }}</dt><dd class="font-medium">{{ payment.expires_at }}</dd></div>
                    <div v-if="payment.last_checked_at"><dt class="text-ink-muted">{{ $t('payments.last_checked') }}</dt><dd class="font-medium">{{ payment.last_checked_at }}</dd></div>
                </dl>

                <dl v-if="Object.keys(payment.gateway_fields).length" class="grid min-w-0 gap-2 rounded-xl bg-surface-muted p-4 text-sm">
                    <div v-for="(value, label) in payment.gateway_fields" :key="label" class="grid min-w-0 gap-1 sm:grid-cols-[180px_minmax(0,1fr)]">
                        <dt class="text-ink-muted">{{ label }}</dt>
                        <dd class="min-w-0 font-mono text-xs break-all">{{ value }}</dd>
                    </div>
                </dl>

                <dl v-if="detailEntries.length" class="grid min-w-0 gap-2 text-sm sm:grid-cols-2">
                    <div v-for="[key, value] in detailEntries" :key="key" class="min-w-0"><dt class="text-ink-muted">{{ key }}</dt><dd class="font-medium break-all">{{ value }}</dd></div>
                </dl>

                <p v-if="payment.note" class="rounded-xl border border-line px-4 py-3 text-sm whitespace-pre-line"><span class="block text-xs text-ink-muted">{{ $t('payments.note') }}</span>{{ payment.note }}</p>
                <a v-if="payment.receipt_url" :href="payment.receipt_url" target="_blank" rel="noopener" class="self-start text-sm font-medium text-brand-700 underline">{{ $t('payments.receipt') }}</a>
            </section>

            <!-- Everything that passed between Neekah and the gateway. -->
            <section class="flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
                <div>
                    <h2 class="font-display text-lg font-semibold">{{ $t('payments.timeline') }}</h2>
                    <p class="text-sm text-ink-muted">{{ $t('payments.timeline_help') }}</p>
                </div>
                <p v-if="!events.length" class="rounded-xl bg-surface-muted p-5 text-center text-sm text-ink-muted">{{ $t('payments.timeline_empty') }}</p>
                <ol class="relative flex min-w-0 flex-col gap-4 border-l border-line pl-5">
                    <li v-for="event in events" :key="event.id" class="relative min-w-0">
                        <span :class="['absolute top-1.5 -left-[26px] size-3 rounded-full ring-4 ring-surface-raised', dot(event)]"></span>
                        <div class="flex min-w-0 flex-wrap items-center gap-2 text-sm">
                            <span class="font-semibold">{{ event.type }}</span>
                            <span v-if="event.verified === true" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">{{ $t('payments.verified') }}</span>
                            <span v-else-if="event.verified === false" class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-800">{{ $t('payments.unverified') }}</span>
                            <span v-if="event.outcome" class="rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-semibold text-ink-muted">{{ event.outcome }}</span>
                            <span v-if="event.http_status" class="rounded-full bg-surface-muted px-2 py-0.5 font-mono text-[11px] text-ink-muted">HTTP {{ event.http_status }}</span>
                        </div>
                        <p class="text-xs text-ink-muted">{{ event.at }}<template v-if="event.by"> · {{ event.by }}</template></p>
                        <p v-if="event.meta?.error" class="mt-1 text-xs text-red-700">{{ event.meta.error }}</p>
                        <details v-if="event.payload || event.meta" class="mt-2 min-w-0 rounded-xl border border-line">
                            <summary class="cursor-pointer px-3 py-2 text-xs font-medium text-ink-muted">{{ $t('payments.raw') }}</summary>
                            <pre v-if="event.payload" class="max-h-80 overflow-auto border-t border-line bg-surface-muted p-3 text-[11px] leading-relaxed">{{ pretty(event.payload) }}</pre>
                            <pre v-if="event.meta" class="max-h-40 overflow-auto border-t border-line p-3 text-[11px] leading-relaxed text-ink-muted">{{ pretty(event.meta) }}</pre>
                        </details>
                    </li>
                </ol>
            </section>
        </div>

        <!-- Putting it right. -->
        <aside class="flex min-w-0 flex-col gap-5 lg:sticky lg:top-6 lg:self-start">
            <section v-if="payment.links.length" class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-5 text-sm">
                <a v-for="link in payment.links" :key="link.url" :href="link.url" class="font-medium text-brand-700 hover:underline">{{ link.label }} →</a>
            </section>

            <section v-if="actions.can_requery || actions.can_attach_invoice" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-display text-base font-semibold">{{ $t('payments.requery_title') }}</h2>
                <p class="text-xs text-ink-muted">{{ actions.can_requery ? $t('payments.requery_help') : $t('payments.requery_unavailable') }}</p>
                <form v-if="actions.can_requery" :action="actions.requery_url" method="POST">
                    <input type="hidden" name="_token" :value="csrf">
                    <button type="submit" class="w-full rounded-full bg-ink px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">↻ {{ $t('payments.requery') }}</button>
                </form>

                <form v-if="actions.can_attach_invoice" :action="actions.invoice_url" method="POST" class="flex flex-col gap-2 border-t border-line pt-3">
                    <input type="hidden" name="_token" :value="csrf">
                    <label class="flex flex-col gap-1.5 text-sm">
                        <span class="font-medium">{{ $t('payments.invoice_label') }}</span>
                        <input name="invoice" required maxlength="100" placeholder="HP-PAY-…" class="rounded-xl border border-line bg-surface px-3 py-2 font-mono text-sm">
                        <span class="text-xs text-ink-muted">{{ $t('payments.invoice_help') }}</span>
                        <span v-if="errors.invoice" class="text-xs text-brand-700">{{ errors.invoice }}</span>
                    </label>
                    <button type="submit" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('payments.invoice_submit') }}</button>
                </form>
            </section>

            <section v-if="actions.can_mark_paid" class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
                <h2 class="font-display text-base font-semibold">{{ $t('payments.mark_paid_title') }}</h2>
                <p class="text-xs text-ink-muted">{{ $t('payments.mark_paid_help') }}</p>
                <form :action="actions.mark_paid_url" method="POST" class="flex flex-col gap-2" @submit="(e) => { if (!confirmPaid) { e.preventDefault(); confirmPaid = true; } }">
                    <input type="hidden" name="_token" :value="csrf">
                    <textarea name="note" required maxlength="255" rows="2" :placeholder="$t('payments.mark_paid_note')" class="rounded-xl border border-line bg-surface px-3 py-2 text-sm"></textarea>
                    <span v-if="errors.note" class="text-xs text-brand-700">{{ errors.note }}</span>
                    <button type="submit" class="rounded-full bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700">{{ confirmPaid ? $t('payments.mark_paid_confirm') : $t('payments.mark_paid') }}</button>
                </form>
            </section>
        </aside>
    </div>
</template>
