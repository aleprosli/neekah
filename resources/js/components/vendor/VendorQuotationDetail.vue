<script setup>
/**
 * What the vendor can do with one quotation, beside the sheet itself (drawn
 * by Blade, x-quotation-sheet): send the link, issue the invoice, record the
 * booking, attach a contract. Nothing here is shown to the client.
 */
import { onMounted, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';

const props = defineProps({
    quotation: { type: Object, required: true },
    csrf: { type: String, required: true },
});

const copied = ref(false);
const copy = async () => {
    try {
        await navigator.clipboard.writeText(props.quotation.links.public);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        // No clipboard (an old browser, or no permission): the link is on
        // screen to copy by hand.
    }
};

// Beside the sheet (lg) the history stays open; above it, on a phone, it
// folds away so the sheet is not pushed far down.
const wide = ref(false);
onMounted(() => (wide.value = window.matchMedia('(min-width: 1024px)').matches));

const button = 'rounded-full border border-line px-4 py-2 text-center text-sm font-medium transition hover:border-brand-400';
const primary = 'rounded-full bg-brand-600 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-brand-700';
</script>

<template>
    <aside class="flex min-w-0 flex-col gap-4">
        <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation.share') }}</p>

            <p v-if="quotation.is_draft" class="rounded-xl bg-amber-50 p-3 text-xs text-amber-900">{{ $t('vendor_quotation.draft_hint') }}</p>

            <UiConfirm
                v-if="quotation.links.send && quotation.is_draft"
                :action="quotation.links.send"
                :title="$t('vendor_quotation.send_title', { number: quotation.number })"
                :message="quotation.has_email ? $t('vendor_quotation.send_message_email', { name: quotation.client.name }) : $t('vendor_quotation.send_message')"
                :confirm-label="$t('vendor_quotation.send_confirm')"
                :trigger-class="primary"
                :csrf="csrf"
            >{{ $t('vendor_quotation.send') }}</UiConfirm>

            <template v-else>
                <div class="flex min-w-0 items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2">
                    <span class="min-w-0 flex-1 truncate font-mono text-xs">{{ quotation.links.public }}</span>
                    <button type="button" class="shrink-0 text-xs font-semibold text-brand-700" @click="copy">{{ copied ? $t('vendor_quotation.copied') : $t('vendor_quotation.copy') }}</button>
                </div>
                <a :href="quotation.links.whatsapp" target="_blank" rel="noopener" :class="primary">{{ $t('vendor_quotation.whatsapp') }}</a>
                <UiConfirm
                    v-if="quotation.links.send && quotation.has_email"
                    :action="quotation.links.send"
                    :title="$t('vendor_quotation.resend_title')"
                    :message="$t('vendor_quotation.send_message_email', { name: quotation.client.name })"
                    :confirm-label="$t('vendor_quotation.send_confirm')"
                    :trigger-class="button"
                    :csrf="csrf"
                >{{ $t('vendor_quotation.resend') }}</UiConfirm>
            </template>

            <div class="grid grid-cols-2 gap-2">
                <a :href="quotation.links.public" target="_blank" rel="noopener" :class="button">{{ quotation.is_draft ? $t('vendor_quotation.preview') : $t('vendor_quotation.open_public') }}</a>
                <a :href="quotation.links.print" target="_blank" rel="noopener" :class="button">{{ $t('vendor_quotation.print') }}</a>
            </div>
        </section>

        <section v-if="quotation.links.invoice || quotation.invoice || quotation.links.record_booking || quotation.links.booking" class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5">
            <p class="text-xs font-semibold tracking-wide text-emerald-800 uppercase">{{ $t('vendor_quotation.accepted_next') }}</p>

            <form v-if="quotation.links.invoice" :action="quotation.links.invoice" method="POST">
                <input type="hidden" name="_token" :value="csrf">
                <button type="submit" :class="[primary, 'w-full']">{{ $t('vendor_quotation.issue_invoice') }}</button>
            </form>

            <form v-if="quotation.invoice" :action="quotation.invoice.status_url" method="POST" class="flex flex-col gap-2">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">
                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium">{{ $t('vendor_quotation.invoice_status', { number: quotation.invoice.number }) }}</span>
                    <select name="invoice_status" class="nk-select rounded-xl border border-line bg-surface px-3 py-2 pr-9 text-sm" @change="$event.target.form.submit()">
                        <option v-for="status in quotation.invoice.statuses" :key="status.value" :value="status.value" :selected="status.value === quotation.invoice.status">{{ status.label }}</option>
                    </select>
                </label>
                <p class="text-xs text-ink-muted">{{ $t('vendor_quotation.invoice_status_hint') }}</p>
            </form>

            <a v-if="quotation.links.record_booking" :href="quotation.links.record_booking" :class="button">{{ $t('vendor_quotation.record_booking') }}</a>
            <a v-if="quotation.links.booking" :href="quotation.links.booking" :class="button">{{ $t('vendor_quotation.view_booking') }}</a>
        </section>

        <section v-if="quotation.links.contract || quotation.contracts.length" class="flex flex-col gap-3 rounded-2xl border border-line p-5 text-sm">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation.contract') }}</p>
            <a v-for="contract in quotation.contracts" :key="contract.number" :href="contract.url" class="flex justify-between gap-3 rounded-xl border border-line px-3 py-2 transition hover:border-brand-400">
                <span class="font-medium">{{ contract.number }}</span>
                <span class="text-xs text-ink-muted">{{ contract.status }}</span>
            </a>
            <p v-if="!quotation.contracts.length" class="text-xs text-ink-muted">{{ $t('vendor_quotation.contract_hint') }}</p>
            <a v-if="quotation.links.contract" :href="quotation.links.contract" :class="button">{{ $t('vendor_quotation.prepare_contract') }}</a>
        </section>

        <details class="group rounded-2xl border border-line text-sm" :open="wide">
            <summary class="flex cursor-pointer list-none items-center justify-between p-5 text-xs font-semibold tracking-wide text-ink-muted uppercase [&::-webkit-details-marker]:hidden">
                {{ $t('vendor_quotation.history') }}
                <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
            </summary>
            <div class="flex flex-col gap-3 px-5 pb-5">
                <ol class="flex flex-col gap-2">
                    <li v-for="entry in quotation.history" :key="entry.label" class="flex justify-between gap-3">
                        <span>{{ entry.label }}</span>
                        <span class="shrink-0 text-xs text-ink-muted">{{ entry.at }}</span>
                    </li>
                </ol>
                <p v-if="quotation.decline_reason" class="rounded-xl bg-surface-muted p-3 text-xs">“{{ quotation.decline_reason }}”</p>
                <a v-if="quotation.links.enquiry" :href="quotation.links.enquiry" class="text-xs font-medium text-brand-700 underline underline-offset-4">{{ $t('vendor_quotation.view_enquiry') }}</a>
            </div>
        </details>

        <div class="grid grid-cols-2 gap-2">
            <a v-if="quotation.links.edit" :href="quotation.links.edit" :class="[button, quotation.links.destroy ? '' : 'col-span-2']">{{ $t('vendor_quotation.edit') }}</a>
            <form :action="quotation.links.duplicate" method="POST" class="order-last col-span-2">
                <input type="hidden" name="_token" :value="csrf">
                <button type="submit" :class="[button, 'w-full']">{{ $t('vendor_quotation.duplicate') }}</button>
            </form>
            <UiConfirm
                v-if="quotation.links.destroy"
                :action="quotation.links.destroy"
                method="DELETE"
                :title="$t('vendor_quotation.delete_title', { number: quotation.number })"
                :message="$t('vendor_quotation.delete_message')"
                :confirm-label="$t('vendor_quotation.delete_confirm')"
                tone="danger"
                :trigger-class="button"
                :csrf="csrf"
            >{{ $t('vendor_quotation.delete') }}</UiConfirm>
        </div>
    </aside>
</template>
