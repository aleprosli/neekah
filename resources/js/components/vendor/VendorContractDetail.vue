<script setup>
/**
 * What the vendor can do with one contract, beside the contract itself
 * (drawn by Blade, x-contract-sheet): send the link, withdraw it while it is
 * unsigned, copy it to change it. Nothing here is shown to the client.
 */
import { onMounted, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';

const props = defineProps({
    contract: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const copied = ref(false);
const copy = async () => {
    try {
        await navigator.clipboard.writeText(props.contract.links.public);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        // No clipboard: the link is on screen to copy by hand.
    }
};

// Beside the sheet (≥1400px) the history stays open; above it, on a phone,
// it folds away so the sheet is not pushed far down.
const wide = ref(false);
onMounted(() => (wide.value = window.matchMedia('(min-width: 1400px)').matches));

const button = 'rounded-full border border-line px-4 py-2 text-center text-sm font-medium transition hover:border-brand-400';
const primary = 'rounded-full bg-brand-600 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-brand-700';
</script>

<template>
    <aside class="grid min-w-0 items-start gap-4 md:grid-cols-2 min-[1400px]:grid-cols-1">
        <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_contract.share') }}</p>

            <p v-if="contract.is_draft" class="rounded-xl bg-amber-50 p-3 text-xs text-amber-900">{{ $t('vendor_contract.draft_hint') }}</p>

            <UiConfirm
                v-if="contract.links.send && contract.is_draft"
                :action="contract.links.send"
                :title="$t('vendor_contract.send_title', { number: contract.number })"
                :message="contract.has_email ? $t('vendor_contract.send_message_email', { name: contract.client.name }) : $t('vendor_contract.send_message')"
                :confirm-label="$t('vendor_quotation.send_confirm')"
                :trigger-class="primary"
                :csrf="csrf"
            >{{ $t('vendor_contract.send') }}</UiConfirm>

            <template v-else>
                <div class="flex min-w-0 items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2">
                    <span class="min-w-0 flex-1 truncate font-mono text-xs">{{ contract.links.public }}</span>
                    <button type="button" class="shrink-0 text-xs font-semibold text-brand-700" @click="copy">{{ copied ? $t('vendor_quotation.copied') : $t('vendor_quotation.copy') }}</button>
                </div>
                <a v-if="contract.is_sent" :href="contract.links.whatsapp" target="_blank" rel="noopener" :class="primary">{{ $t('vendor_quotation.whatsapp') }}</a>
                <UiConfirm
                    v-if="contract.links.send && contract.has_email"
                    :action="contract.links.send"
                    :title="$t('vendor_quotation.resend_title')"
                    :message="$t('vendor_contract.send_message_email', { name: contract.client.name })"
                    :confirm-label="$t('vendor_quotation.send_confirm')"
                    :trigger-class="button"
                    :csrf="csrf"
                >{{ $t('vendor_quotation.resend') }}</UiConfirm>
            </template>

            <div class="grid grid-cols-2 gap-2">
                <a :href="contract.links.public" target="_blank" rel="noopener" :class="button">{{ contract.is_draft ? $t('vendor_quotation.preview') : $t('vendor_quotation.open_public') }}</a>
                <a :href="contract.links.print" target="_blank" rel="noopener" :class="button">{{ $t('vendor_quotation.print') }}</a>
            </div>
        </section>

        <details class="group rounded-2xl border border-line text-sm" :open="wide">
            <summary class="flex cursor-pointer list-none items-center justify-between p-5 text-xs font-semibold tracking-wide text-ink-muted uppercase [&::-webkit-details-marker]:hidden">
                {{ $t('vendor_quotation.history') }}
                <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
            </summary>
            <div class="flex flex-col gap-3 px-5 pb-5">
                <ol class="flex flex-col gap-2">
                    <li v-for="entry in contract.history" :key="entry.label" class="flex justify-between gap-3">
                        <span>{{ entry.label }}</span>
                        <span class="shrink-0 text-xs text-ink-muted">{{ entry.at }}</span>
                    </li>
                </ol>
                <p v-if="contract.void_reason" class="rounded-xl bg-surface-muted p-3 text-xs">“{{ contract.void_reason }}”</p>
                <p v-if="contract.content_hash" class="text-xs break-all text-ink-muted">{{ $t('vendor_contract.hash') }}: <span class="font-mono">{{ contract.content_hash }}</span></p>
                <a v-if="contract.quotation" :href="contract.quotation.url" class="text-xs font-medium text-brand-700 underline underline-offset-4">{{ $t('vendor_contract.view_quotation', { number: contract.quotation.number }) }}</a>
            </div>
        </details>

        <details v-if="contract.links.void" class="rounded-2xl border border-line bg-surface-raised">
            <summary class="cursor-pointer list-none px-5 py-4 text-sm font-medium text-ink-muted [&::-webkit-details-marker]:hidden">{{ $t('vendor_contract.void_title') }}</summary>
            <form :action="contract.links.void" method="POST" class="flex flex-col gap-3 border-t border-line p-5">
                <input type="hidden" name="_token" :value="csrf">
                <p class="text-xs text-ink-muted">{{ $t('vendor_contract.void_body') }}</p>
                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium">{{ $t('vendor_contract.void_reason') }}</span>
                    <textarea name="reason" rows="2" maxlength="500" class="rounded-xl border border-line bg-surface px-3 py-2 text-sm focus:border-brand-400 focus:outline-none"></textarea>
                </label>
                <span v-if="errors.reason" class="text-xs text-brand-700">{{ errors.reason }}</span>
                <button type="submit" class="w-fit rounded-full border border-brand-600 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ $t('vendor_contract.void_submit') }}</button>
            </form>
        </details>

        <div class="flex flex-wrap gap-2">
            <a v-if="contract.links.edit" :href="contract.links.edit" :class="button">{{ $t('vendor_quotation.edit') }}</a>
            <form :action="contract.links.duplicate" method="POST">
                <input type="hidden" name="_token" :value="csrf">
                <button type="submit" :class="button">{{ $t('vendor_quotation.duplicate') }}</button>
            </form>
            <UiConfirm
                v-if="contract.links.destroy"
                :action="contract.links.destroy"
                method="DELETE"
                :title="$t('vendor_contract.delete_title', { number: contract.number })"
                :message="$t('vendor_quotation.delete_message')"
                :confirm-label="$t('vendor_quotation.delete_confirm')"
                tone="danger"
                :trigger-class="button"
                :csrf="csrf"
            >{{ $t('vendor_quotation.delete') }}</UiConfirm>
        </div>
    </aside>
</template>
