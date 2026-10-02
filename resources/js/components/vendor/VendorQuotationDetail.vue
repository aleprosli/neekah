<script setup>
/**
 * One quotation as the vendor sees it: what was quoted, where it stands with
 * the client, and the next step — send the link, issue the invoice, record
 * the booking. The client's own page is the public link; nothing here is
 * shown to them.
 */
import { ref } from 'vue';
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

const button = 'rounded-full border border-line px-4 py-2 text-center text-sm font-medium transition hover:border-brand-400';
const primary = 'rounded-full bg-brand-600 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-brand-700';
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="flex min-w-0 flex-col gap-6">
            <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2 [&>div]:min-w-0">
                <div>
                    <dt class="text-ink-muted">{{ $t('vendor_quotation.client') }}</dt>
                    <dd class="font-semibold">{{ quotation.client.name }}</dd>
                    <dd class="break-words text-ink-muted">{{ quotation.client.contact }}</dd>
                </div>
                <div><dt class="text-ink-muted">{{ $t('vendor_quotation.valid_until') }}</dt><dd class="font-semibold">{{ quotation.valid_until }}</dd></div>
                <div v-if="quotation.event_date"><dt class="text-ink-muted">{{ $t('vendor_quotation.event_date') }}</dt><dd class="font-semibold">{{ quotation.event_date }}</dd></div>
                <div v-if="quotation.event_location"><dt class="text-ink-muted">{{ $t('vendor_quotation.event_location') }}</dt><dd class="font-semibold">{{ quotation.event_location }}</dd></div>
            </dl>

            <section class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation.items') }}</p>
                <ul class="mt-3 flex flex-col divide-y divide-line text-sm">
                    <li v-for="(item, index) in quotation.items" :key="index" class="flex items-start justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="font-medium">{{ item.name }}</p>
                            <p class="text-xs text-ink-muted">{{ item.kind }} · {{ item.quantity }} × {{ item.unit_price }}</p>
                        </div>
                        <span class="shrink-0 font-medium tabular-nums">{{ item.line_total }}</span>
                    </li>
                </ul>
                <dl class="mt-2 ml-auto flex max-w-xs flex-col gap-1.5 border-t border-line pt-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ $t('vendor_quotation.subtotal') }}</dt><dd class="tabular-nums">{{ quotation.subtotal }}</dd></div>
                    <div v-if="quotation.discount" class="flex justify-between gap-4"><dt class="text-ink-muted">{{ $t('vendor_quotation.discount') }}</dt><dd class="tabular-nums">− {{ quotation.discount }}</dd></div>
                    <div class="flex justify-between gap-4 font-semibold"><dt>{{ $t('vendor_quotation.total') }}</dt><dd class="tabular-nums">{{ quotation.total }}</dd></div>
                    <template v-if="quotation.deposit">
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ $t('vendor_quotation.deposit') }}</dt><dd class="tabular-nums">{{ quotation.deposit }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ $t('vendor_quotation.balance') }}</dt><dd class="tabular-nums">{{ quotation.balance }}</dd></div>
                    </template>
                </dl>
            </section>

            <section v-if="quotation.terms" class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation.terms') }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-ink-muted">{{ quotation.terms }}</p>
            </section>

            <section v-if="quotation.notes" class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation.notes') }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ quotation.notes }}</p>
            </section>
        </div>

        <aside class="flex min-w-0 flex-col gap-4 lg:self-start">
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

                <a :href="quotation.links.public" target="_blank" rel="noopener" :class="button">{{ quotation.is_draft ? $t('vendor_quotation.preview') : $t('vendor_quotation.open_public') }}</a>
                <a :href="quotation.links.print" target="_blank" rel="noopener" :class="button">{{ $t('vendor_quotation.print') }}</a>
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

            <section class="flex flex-col gap-3 rounded-2xl border border-line p-5 text-sm">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation.history') }}</p>
                <ol class="flex flex-col gap-2">
                    <li v-for="entry in quotation.history" :key="entry.label" class="flex justify-between gap-3">
                        <span>{{ entry.label }}</span>
                        <span class="shrink-0 text-xs text-ink-muted">{{ entry.at }}</span>
                    </li>
                </ol>
                <p v-if="quotation.decline_reason" class="rounded-xl bg-surface-muted p-3 text-xs">“{{ quotation.decline_reason }}”</p>
                <a v-if="quotation.links.enquiry" :href="quotation.links.enquiry" class="text-xs font-medium text-brand-700 underline underline-offset-4">{{ $t('vendor_quotation.view_enquiry') }}</a>
            </section>

            <div class="flex flex-wrap gap-2">
                <a v-if="quotation.links.edit" :href="quotation.links.edit" :class="button">{{ $t('vendor_quotation.edit') }}</a>
                <form :action="quotation.links.duplicate" method="POST">
                    <input type="hidden" name="_token" :value="csrf">
                    <button type="submit" :class="button">{{ $t('vendor_quotation.duplicate') }}</button>
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
    </div>
</template>
