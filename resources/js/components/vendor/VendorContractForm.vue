<script setup>
/**
 * Writing a contract. On the left, the document itself: it looks like the
 * contract the client will read and sign (x-contract-sheet), and every part
 * the vendor can change is typed straight into it. On the right, the steps,
 * the quotation it carries, and saving. On a phone the panel follows the
 * document and the save buttons stay at the bottom.
 *
 * Step two of three: the quotation was picked first (its sums are shown on
 * the document, read-only), the five standard sections arrive filled with
 * the vendor's saved text or a starter text to edit, and "Simpan & hantar"
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
const card = 'flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5';
const cardTitle = 'text-xs font-semibold tracking-wide text-ink-muted uppercase';
const primary = 'rounded-full bg-brand-600 px-6 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-700';
const secondary = 'rounded-full border border-line bg-white px-5 py-2.5 text-center text-sm font-semibold transition hover:border-brand-400';
const steps = ['step_quotation', 'step_write', 'step_send'];
</script>

<template>
    <form :action="action" method="POST" class="grid gap-6 break-words lg:grid-cols-12 lg:items-start">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">
        <input v-if="quotation" type="hidden" name="quotation_id" :value="quotation.id">
        <input type="hidden" name="save_as_default" :value="form.save_as_default ? 1 : 0">

        <!-- The document -->
        <div class="flex min-w-0 flex-col gap-3 lg:col-span-8">
            <article class="@container relative w-full overflow-hidden rounded-2xl bg-white text-ink shadow-[0_1px_3px_rgb(0_0_0/0.08),0_12px_40px_-12px_rgb(0_0_0/0.15)]">
                <div class="h-2 bg-gradient-to-r from-brand-700 via-brand-500 to-gold-400"></div>

                <div class="p-5 @lg:p-8 @2xl:p-12">
                    <header class="flex flex-col-reverse gap-6 @xl:flex-row @xl:items-start @xl:justify-between">
                        <div class="flex min-w-0 items-start gap-4">
                            <img v-if="vendor.logo" :src="vendor.logo" alt="" class="size-14 shrink-0 rounded-2xl object-cover">
                            <p class="min-w-0 font-display text-xl font-semibold break-words @xl:text-2xl">{{ vendor.name }}</p>
                        </div>
                        <div class="@xl:text-right">
                            <p class="font-display text-3xl font-semibold tracking-wide text-brand-700 uppercase @2xl:text-4xl">{{ $t('vendor_contract_form.title') }}</p>
                            <p class="mt-1 font-mono text-sm font-semibold">{{ number || $t('vendor_quotation_form.number_on_save') }}</p>
                        </div>
                    </header>

                    <section class="mt-8 grid gap-6 border-y border-line py-5 @xl:grid-cols-2">
                        <div class="flex min-w-0 flex-col gap-1">
                            <p :class="label">{{ $t('vendor_contract_form.client') }}</p>
                            <input v-model="form.client_name" name="client_name" required maxlength="120" :aria-label="$t('vendor_quotation_form.client_name')" :placeholder="$t('vendor_quotation_form.client_name')" :class="[ink, 'mt-1 text-base font-semibold']">
                            <span v-if="errors.client_name" class="text-xs text-red-700">{{ errors.client_name }}</span>
                            <input v-model="form.client_phone" name="client_phone" type="tel" maxlength="30" :aria-label="$t('vendor_quotation_form.client_phone')" :placeholder="$t('vendor_quotation_form.client_phone')" :class="[ink, 'text-sm']">
                            <input v-model="form.client_email" name="client_email" type="email" :aria-label="$t('vendor_quotation_form.client_email')" :placeholder="$t('vendor_quotation_form.client_email_placeholder')" :class="[ink, 'text-sm']">
                            <span v-if="errors.client_email" class="text-xs text-red-700">{{ errors.client_email }}</span>
                        </div>

                        <dl class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-1.5 self-start text-sm @xl:justify-self-end">
                            <dt class="text-ink-muted">{{ $t('vendor_quotation_form.event_date') }}</dt>
                            <dd><input v-model="form.event_date" name="event_date" type="date" :aria-label="$t('vendor_quotation_form.event_date')" :class="[ink, 'font-medium @xl:text-right']"></dd>
                        </dl>
                    </section>

                    <section v-if="quotation" class="mt-6 rounded-2xl border border-line p-4 @xl:p-5">
                        <p :class="label">{{ $t('vendor_contract_form.quotation_attached', { number: quotation.number }) }}</p>
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
                    </section>

                    <p v-if="errors.sections" class="mt-4 text-sm text-red-700">{{ errors.sections }}</p>

                    <ol class="mt-6 flex flex-col gap-5">
                        <li v-for="(item, index) in sections" :key="item.id" class="flex min-w-0 flex-col gap-1">
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

                    <button type="button" class="mt-4 w-full rounded-xl border border-dashed border-line px-3 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="add">+ {{ $t('vendor_contract_form.add_section') }}</button>

                    <section class="mt-8 grid gap-6 border-t border-line pt-5 text-sm @xl:grid-cols-2">
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

            <p class="text-xs text-ink-muted">{{ $t('vendor_contract_form.sheet_hint') }}</p>
        </div>

        <!-- The panel -->
        <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-24 lg:col-span-4">
            <section v-if="changeQuotationUrl" :class="card">
                <ol class="flex flex-col gap-2 text-sm">
                    <li v-for="(step, index) in steps" :key="step" class="flex items-center gap-3" :class="index === 1 ? 'font-semibold text-brand-700' : 'text-ink-muted'">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full text-xs" :class="index === 0 ? 'border border-brand-600 text-brand-700' : index === 1 ? 'bg-brand-600 text-white' : 'border border-line'">{{ index === 0 ? '✓' : index + 1 }}</span>
                        {{ $t(`vendor_contract_form.${step}`) }}
                    </li>
                </ol>
            </section>

            <section :class="[card, 'hidden lg:flex']">
                <div class="flex flex-col gap-2">
                    <button v-if="canSend" type="submit" name="send" value="1" :class="primary">{{ $t('vendor_contract_form.save_and_send') }}</button>
                    <button type="submit" :class="secondary">{{ $t('vendor_quotation_form.save_draft') }}</button>
                    <a :href="cancelUrl" class="rounded-full px-5 py-2 text-center text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('vendor_quotation_form.cancel') }}</a>
                </div>
                <p class="text-xs text-ink-muted">{{ $t('vendor_contract_form.save_hint') }}</p>
            </section>

            <section :class="card">
                <p :class="cardTitle">{{ $t('vendor_contract_form.quotation_panel') }}</p>
                <template v-if="quotation">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface px-3 py-2.5 text-sm">
                        <span class="font-mono font-semibold">{{ quotation.number }}</span>
                        <span class="font-semibold tabular-nums">{{ quotation.total }}</span>
                    </div>
                    <p class="text-xs text-ink-muted">{{ $t('vendor_contract_form.quotation_hint') }}</p>
                    <a v-if="changeQuotationUrl" :href="changeQuotationUrl" class="text-sm font-medium text-brand-700 underline underline-offset-4">{{ $t('vendor_contract_form.change') }}</a>
                </template>
                <template v-else>
                    <p class="text-sm text-ink-muted">{{ $t('vendor_contract_form.no_quotation_attached') }}</p>
                    <a v-if="changeQuotationUrl" :href="changeQuotationUrl" class="rounded-xl border border-dashed border-line px-3 py-2.5 text-center text-sm font-medium transition hover:border-brand-400">+ {{ $t('vendor_contract_form.attach_quotation') }}</a>
                </template>
            </section>

            <section :class="card">
                <p :class="cardTitle">{{ $t('vendor_contract_form.template') }}</p>
                <label class="flex items-start gap-2 text-sm">
                    <input v-model="form.save_as_default" type="checkbox" class="mt-0.5 size-4 rounded border-line text-brand-600">
                    <span>{{ $t('vendor_contract_form.save_as_default') }}</span>
                </label>
            </section>
        </aside>

        <!-- Below lg the panel follows the document, so the save buttons stay in reach at the bottom. -->
        <div class="sticky bottom-3 z-10 flex flex-wrap items-center justify-end gap-2 rounded-2xl border border-line bg-surface-raised/95 px-4 py-3 shadow-sm backdrop-blur lg:hidden">
            <a :href="cancelUrl" class="rounded-full px-3 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('vendor_quotation_form.cancel') }}</a>
            <button type="submit" :class="secondary">{{ $t('vendor_quotation_form.save_draft') }}</button>
            <button v-if="canSend" type="submit" name="send" value="1" :class="primary">{{ $t('vendor_contract_form.save_and_send') }}</button>
        </div>
    </form>
</template>
