<script setup>
/**
 * Writing a contract on the document itself: the page looks like the
 * contract the client will read and sign (x-contract-sheet), and every part
 * the vendor can change is typed straight into it.
 *
 * Step two of three: the quotation was picked first (its sums are shown
 * here, read-only), the five standard sections arrive filled with the
 * vendor's saved text or a starter text to edit, and "Simpan & hantar"
 * finishes it in one go.
 */
import { ref } from 'vue';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    cancelUrl: { type: String, required: true },
    number: { type: String, default: null },
    vendor: { type: Object, required: true },
    /** The attached quotation's summary, or null. */
    quotation: { type: Object, default: null },
    /** Back to step one, to pick another quotation; null when editing. */
    changeQuotationUrl: { type: String, default: null },
    contract: { type: Object, required: true },
    saveAsDefault: { type: Boolean, default: false },
    canSend: { type: Boolean, default: true },
    standardKeys: { type: Array, default: () => [] },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
    old: { type: Object, default: () => ({}) },
});

const hasOld = Object.keys(props.old).length > 0;
const pick = (key) => (hasOld ? (props.old[key] ?? '') : (props.contract[key] ?? ''));

const form = ref({
    client_name: pick('client_name'),
    client_phone: pick('client_phone'),
    client_email: pick('client_email'),
    event_date: pick('event_date'),
    save_as_default: hasOld ? Boolean(Number(props.old.save_as_default)) : props.saveAsDefault,
});

let nextKey = 0;
const section = (item = {}) => ({ id: nextKey++, key: item.key ?? '', title: item.title ?? '', body: item.body ?? '' });
const sections = ref((hasOld ? Object.values(props.old.sections ?? {}) : props.contract.sections).map(section));

const add = () => sections.value.push(section());
const remove = (index) => sections.value.splice(index, 1);
const isStandard = (key) => props.standardKeys.includes(key);

const sectionError = (index, field) => props.errors[`sections.${index}.${field}`];

const ink = 'w-full min-w-0 rounded-md border-0 border-b border-dashed border-line bg-transparent px-1.5 py-1 transition placeholder:text-ink-muted/60 hover:bg-ivory focus:border-solid focus:border-brand-400 focus:bg-white focus:ring-0 focus:outline-none';
const label = 'text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase';
</script>

<template>
    <form :action="action" method="POST" class="flex flex-col gap-4">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">

        <input v-if="quotation" type="hidden" name="quotation_id" :value="quotation.id">

        <ol v-if="changeQuotationUrl" class="mx-auto flex w-full max-w-[210mm] flex-wrap items-center gap-3 text-xs font-medium text-ink-muted">
            <li class="flex items-center gap-2"><span class="flex size-6 items-center justify-center rounded-full border border-brand-600 text-brand-700">✓</span>{{ $t('vendor_contract_form.step_quotation') }}</li>
            <li aria-hidden="true" class="h-px w-6 bg-line"></li>
            <li class="flex items-center gap-2 text-brand-700"><span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-white">2</span>{{ $t('vendor_contract_form.step_write') }}</li>
            <li aria-hidden="true" class="h-px w-6 bg-line"></li>
            <li class="flex items-center gap-2"><span class="flex size-6 items-center justify-center rounded-full border border-line">3</span>{{ $t('vendor_contract_form.step_send') }}</li>
        </ol>

        <p class="mx-auto w-full max-w-[210mm] text-xs text-ink-muted">{{ $t('vendor_contract_form.sheet_hint') }}</p>

        <article class="relative mx-auto w-full max-w-[210mm] overflow-hidden rounded-2xl bg-white text-ink shadow-[0_1px_3px_rgb(0_0_0/0.08),0_12px_40px_-12px_rgb(0_0_0/0.15)]">
            <div class="h-2 bg-gradient-to-r from-brand-700 via-brand-500 to-gold-400"></div>

            <div class="p-5 sm:p-10">
                <header class="flex flex-col-reverse gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <img v-if="vendor.logo" :src="vendor.logo" alt="" class="size-14 shrink-0 rounded-2xl object-cover">
                        <p class="min-w-0 font-display text-2xl font-semibold break-words">{{ vendor.name }}</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="font-display text-3xl font-semibold tracking-wide text-brand-700 uppercase sm:text-4xl">{{ $t('vendor_contract_form.title') }}</p>
                        <p class="mt-1 font-mono text-sm font-semibold">{{ number || $t('vendor_quotation_form.number_on_save') }}</p>
                    </div>
                </header>

                <section class="mt-8 grid gap-6 border-y border-line py-5 sm:grid-cols-2">
                    <div class="flex min-w-0 flex-col gap-1">
                        <p :class="label">{{ $t('vendor_contract_form.client') }}</p>
                        <input v-model="form.client_name" name="client_name" required maxlength="120" :aria-label="$t('vendor_quotation_form.client_name')" :placeholder="$t('vendor_quotation_form.client_name')" :class="[ink, 'mt-1 text-base font-semibold']">
                        <span v-if="errors.client_name" class="text-xs text-red-700">{{ errors.client_name }}</span>
                        <input v-model="form.client_phone" name="client_phone" type="tel" maxlength="30" :aria-label="$t('vendor_quotation_form.client_phone')" :placeholder="$t('vendor_quotation_form.client_phone')" :class="[ink, 'text-sm']">
                        <input v-model="form.client_email" name="client_email" type="email" :aria-label="$t('vendor_quotation_form.client_email')" :placeholder="$t('vendor_quotation_form.client_email_placeholder')" :class="[ink, 'text-sm']">
                        <span v-if="errors.client_email" class="text-xs text-red-700">{{ errors.client_email }}</span>
                    </div>

                    <dl class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-1.5 text-sm sm:justify-self-end">
                        <dt class="text-ink-muted">{{ $t('vendor_quotation_form.event_date') }}</dt>
                        <dd><input v-model="form.event_date" name="event_date" type="date" :aria-label="$t('vendor_quotation_form.event_date')" :class="[ink, 'font-medium sm:text-right']"></dd>
                    </dl>
                </section>

                <section v-if="quotation" class="mt-6 rounded-2xl border border-line p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p :class="label">{{ $t('vendor_contract_form.quotation_attached', { number: quotation.number }) }}</p>
                        <a v-if="changeQuotationUrl" :href="changeQuotationUrl" class="text-xs font-medium text-brand-700 underline underline-offset-4">{{ $t('vendor_contract_form.change') }}</a>
                    </div>
                    <ul class="mt-3 flex flex-col gap-1.5 text-sm">
                        <li v-for="(item, index) in quotation.items" :key="index" class="flex justify-between gap-4">
                            <span class="min-w-0">{{ item.name }}<template v-if="item.quantity > 1"> × {{ item.quantity }}</template></span>
                            <span class="shrink-0 tabular-nums">{{ item.line_total }}</span>
                        </li>
                    </ul>
                    <dl class="mt-3 flex flex-col gap-1 border-t border-line pt-3 text-sm">
                        <div class="flex justify-between gap-4 font-semibold"><dt>{{ $t('vendor_quotation_form.total') }}</dt><dd class="tabular-nums">{{ quotation.total }}</dd></div>
                        <template v-if="quotation.deposit">
                            <div class="flex justify-between gap-4 text-ink-muted"><dt>{{ $t('vendor_quotation_form.deposit') }}</dt><dd class="tabular-nums">{{ quotation.deposit }}</dd></div>
                            <div class="flex justify-between gap-4 text-ink-muted"><dt>{{ $t('vendor_quotation_form.balance') }}</dt><dd class="tabular-nums">{{ quotation.balance }}</dd></div>
                        </template>
                    </dl>
                    <p class="mt-3 text-xs text-ink-muted">{{ $t('vendor_contract_form.quotation_hint') }}</p>
                </section>
                <a v-else-if="changeQuotationUrl" :href="changeQuotationUrl" class="mt-6 block rounded-2xl border border-dashed border-line p-4 text-center text-sm font-medium text-ink-muted transition hover:border-brand-400 hover:text-ink">+ {{ $t('vendor_contract_form.attach_quotation') }}</a>

                <p v-if="errors.sections" class="mt-4 text-sm text-red-700">{{ errors.sections }}</p>

                <ol class="mt-6 flex flex-col gap-5">
                    <li v-for="(item, index) in sections" :key="item.id" class="group flex min-w-0 flex-col gap-1">
                        <input type="hidden" :name="`sections[${index}][key]`" :value="item.key">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ index + 1 }}.</span>
                            <input v-model="item.title" :name="`sections[${index}][title]`" required maxlength="150" :aria-label="$t('vendor_contract_form.section_title')" :placeholder="$t('vendor_contract_form.section_title')" :class="[ink, 'font-semibold']">
                            <button type="button" class="flex size-6 shrink-0 items-center justify-center rounded-full text-ink-muted transition hover:bg-red-50 hover:text-red-700" :aria-label="$t('vendor_quotation_form.remove')" @click="remove(index)">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                            </button>
                        </div>
                        <span v-if="sectionError(index, 'title')" class="text-xs text-red-700">{{ sectionError(index, 'title') }}</span>
                        <textarea v-model="item.body" :name="`sections[${index}][body]`" rows="4" maxlength="10000" :aria-label="item.title || $t('vendor_contract_form.section_body')" :placeholder="isStandard(item.key) ? $t(`vendor_contract_form.placeholders.${item.key}`) : $t('vendor_contract_form.section_body')" :class="[ink, 'resize-y text-sm leading-relaxed text-ink-muted']"></textarea>
                        <span v-if="sectionError(index, 'body')" class="text-xs text-red-700">{{ sectionError(index, 'body') }}</span>
                    </li>
                </ol>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed border-line p-3">
                    <button type="button" class="rounded-full border border-line bg-white px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="add">+ {{ $t('vendor_contract_form.add_section') }}</button>
                    <label class="flex items-center gap-2 text-xs text-ink-muted">
                        <input type="hidden" name="save_as_default" value="0">
                        <input v-model="form.save_as_default" type="checkbox" name="save_as_default" value="1" class="size-4 rounded border-line text-brand-600">
                        <span>{{ $t('vendor_contract_form.save_as_default') }}</span>
                    </label>
                </div>

                <section class="mt-8 grid gap-6 border-t border-line pt-5 text-sm sm:grid-cols-2">
                    <div>
                        <p :class="label">{{ $t('vendor_contract_form.vendor_side') }}</p>
                        <p class="mt-2 text-ink-muted">{{ $t('vendor_contract_form.vendor_side_hint') }}</p>
                    </div>
                    <div>
                        <p :class="label">{{ $t('vendor_contract_form.client_side') }}</p>
                        <p class="mt-2 text-ink-muted">{{ $t('vendor_contract_form.client_side_hint') }}</p>
                    </div>
                </section>
            </div>
        </article>

        <div class="sticky bottom-3 z-10 mx-auto flex w-full max-w-[210mm] flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface-raised/95 px-4 py-3 shadow-sm backdrop-blur">
            <p class="text-xs text-ink-muted">{{ $t('vendor_contract_form.save_hint') }}</p>
            <div class="flex flex-wrap gap-2">
                <a :href="cancelUrl" class="rounded-full px-4 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('vendor_quotation_form.cancel') }}</a>
                <button type="submit" class="rounded-full border border-line bg-white px-5 py-2.5 text-sm font-semibold transition hover:border-brand-400">{{ $t('vendor_quotation_form.save_draft') }}</button>
                <button v-if="canSend" type="submit" name="send" value="1" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('vendor_contract_form.save_and_send') }}</button>
            </div>
        </div>
    </form>
</template>
