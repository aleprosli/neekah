<script setup>
/**
 * Writing a quotation. On the left, the sheet itself: it looks like the
 * invoice the client will get (x-quotation-sheet), and the client, lines,
 * notes and terms are typed straight into it. On the right, the choices
 * around it: what to add, the discount and deposit, and saving. On a phone
 * the panel follows the sheet and the save buttons stay at the bottom.
 *
 * The sums shown follow the vendor as they type; the server works them out
 * again on save (Quotation::recalculate) and never takes them from here.
 */
import { computed, ref } from 'vue';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    cancelUrl: { type: String, required: true },
    number: { type: String, default: null },
    canSend: { type: Boolean, default: true },
    vendor: { type: Object, required: true },
    packages: { type: Array, required: true },
    quotation: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
    old: { type: Object, default: () => ({}) },
});

const hasOld = Object.keys(props.old).length > 0;
const pick = (key) => (hasOld ? (props.old[key] ?? '') : (props.quotation[key] ?? ''));

const form = ref({
    client_name: pick('client_name'),
    client_phone: pick('client_phone'),
    client_email: pick('client_email'),
    event_date: pick('event_date'),
    event_location: pick('event_location'),
    valid_until: pick('valid_until'),
    discount_type: pick('discount_type') || 'fixed',
    discount_value: pick('discount_value') || 0,
    deposit_type: pick('deposit_type') || 'percent',
    deposit_value: pick('deposit_value') || 0,
    terms: pick('terms'),
    notes: pick('notes'),
    save_terms_as_default: hasOld ? Boolean(Number(props.old.save_terms_as_default)) : false,
});

let nextKey = 0;
const line = (item = {}) => ({
    key: nextKey++,
    package_id: item.package_id ?? '',
    name: item.name ?? '',
    description: item.description ?? '',
    quantity: item.quantity ?? 1,
    unit_price: item.unit_price ?? 0,
});

const items = ref((hasOld ? Object.values(props.old.items ?? {}) : props.quotation.items).map(line));

const packageOf = (id) => props.packages.find((p) => String(p.id) === String(id));
const addPackage = (chosen) => items.value.push(line({ package_id: chosen.id, name: chosen.name, unit_price: chosen.price }));
const addAddon = () => items.value.push(line());
const remove = (index) => items.value.splice(index, 1);
const timesAdded = (id) => items.value.filter((item) => String(item.package_id) === String(id)).length;

const money = (amount) => `RM${Number(amount || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const round = (amount) => Math.round(amount * 100) / 100;
const lineTotal = (item) => Math.max(1, Number(item.quantity) || 1) * Math.max(0, Number(item.unit_price) || 0);

/** The same order Quotation::recalculate() works in. */
const sums = computed(() => {
    const subtotal = round(items.value.reduce((sum, item) => sum + lineTotal(item), 0));
    const discountValue = Math.max(0, Number(form.value.discount_value) || 0);
    const discount = round(form.value.discount_type === 'percent' ? (subtotal * Math.min(100, discountValue)) / 100 : Math.min(discountValue, subtotal));
    const total = round(subtotal - discount);
    const depositValue = Math.max(0, Number(form.value.deposit_value) || 0);
    const deposit = round(form.value.deposit_type === 'percent' ? (total * Math.min(100, depositValue)) / 100 : Math.min(depositValue, total));

    return { subtotal, discount, total, deposit, balance: round(total - deposit) };
});

const itemError = (index, field) => props.errors[`items.${index}.${field}`];

/** A field typed straight onto the sheet: no box, a dashed rule underneath. */
const ink = 'w-full min-w-0 rounded-md border-0 border-b border-dashed border-line bg-transparent px-1.5 py-1 transition placeholder:text-ink-muted/60 hover:bg-ivory focus:border-solid focus:border-brand-400 focus:bg-white focus:ring-0 focus:outline-none';
const label = 'text-[11px] font-semibold tracking-[0.18em] text-gold-600 uppercase';
const card = 'flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5';
const cardTitle = 'text-xs font-semibold tracking-wide text-ink-muted uppercase';
const field = 'w-full min-w-0 rounded-xl border border-line bg-surface px-3 py-2 text-sm tabular-nums focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none';
const segment = (active) => ['flex-1 px-3 py-2 text-xs font-semibold transition', active ? 'bg-brand-600 text-white' : 'text-ink-muted hover:text-ink'];
const primary = 'rounded-full bg-brand-600 px-6 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-700';
const secondary = 'rounded-full border border-line bg-white px-5 py-2.5 text-center text-sm font-semibold transition hover:border-brand-400';
</script>

<template>
    <form :action="action" method="POST" class="grid gap-6 break-words lg:grid-cols-12 lg:items-start">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">
        <input v-if="quotation.enquiry_id" type="hidden" name="enquiry_id" :value="quotation.enquiry_id">
        <input type="hidden" name="discount_type" :value="form.discount_type">
        <input type="hidden" name="discount_value" :value="form.discount_value">
        <input type="hidden" name="deposit_type" :value="form.deposit_type">
        <input type="hidden" name="deposit_value" :value="form.deposit_value">

        <!-- The sheet -->
        <div class="flex min-w-0 flex-col gap-3 lg:col-span-8">
            <article class="@container relative w-full overflow-hidden rounded-2xl bg-white text-ink shadow-[0_1px_3px_rgb(0_0_0/0.08),0_12px_40px_-12px_rgb(0_0_0/0.15)]">
                <div class="h-2 bg-gradient-to-r from-brand-700 via-brand-500 to-gold-400"></div>

                <div class="p-5 @lg:p-8 @2xl:p-12">
                    <header class="flex flex-col-reverse gap-6 @xl:flex-row @xl:items-start @xl:justify-between">
                        <div class="flex min-w-0 items-start gap-4">
                            <img v-if="vendor.logo" :src="vendor.logo" alt="" class="size-14 shrink-0 rounded-2xl object-cover">
                            <div class="min-w-0">
                                <p class="font-display text-xl font-semibold break-words @xl:text-2xl">{{ vendor.name }}</p>
                                <p v-for="row in vendor.lines" :key="row" class="text-sm break-words text-ink-muted">{{ row }}</p>
                            </div>
                        </div>
                        <div class="@xl:text-right">
                            <p class="font-display text-3xl font-semibold tracking-wide text-brand-700 uppercase @2xl:text-4xl">{{ $t('vendor_quotation_form.title') }}</p>
                            <p class="mt-1 font-mono text-sm font-semibold">{{ number || $t('vendor_quotation_form.number_on_save') }}</p>
                        </div>
                    </header>

                    <section class="mt-8 grid gap-6 border-y border-line py-5 @xl:grid-cols-2">
                        <div class="flex min-w-0 flex-col gap-1">
                            <p :class="label">{{ $t('vendor_quotation_form.prepared_for') }}</p>
                            <input v-model="form.client_name" name="client_name" required maxlength="120" :aria-label="$t('vendor_quotation_form.client_name')" :placeholder="$t('vendor_quotation_form.client_name')" :class="[ink, 'mt-1 text-base font-semibold']">
                            <span v-if="errors.client_name" class="text-xs text-red-700">{{ errors.client_name }}</span>
                            <input v-model="form.client_phone" name="client_phone" type="tel" maxlength="30" :aria-label="$t('vendor_quotation_form.client_phone')" :placeholder="$t('vendor_quotation_form.client_phone')" :class="[ink, 'text-sm']">
                            <input v-model="form.client_email" name="client_email" type="email" :aria-label="$t('vendor_quotation_form.client_email')" :placeholder="$t('vendor_quotation_form.client_email_placeholder')" :class="[ink, 'text-sm']">
                            <span v-if="errors.client_email" class="text-xs text-red-700">{{ errors.client_email }}</span>
                        </div>

                        <dl class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-1.5 text-sm @xl:justify-self-end">
                            <dt class="text-ink-muted">{{ $t('vendor_quotation_form.valid_until') }}</dt>
                            <dd><input v-model="form.valid_until" name="valid_until" type="date" required :aria-label="$t('vendor_quotation_form.valid_until')" :class="[ink, 'font-medium @xl:text-right']"></dd>
                            <dd v-if="errors.valid_until" class="col-span-2 text-xs text-red-700">{{ errors.valid_until }}</dd>
                            <dt class="text-ink-muted">{{ $t('vendor_quotation_form.event_date') }}</dt>
                            <dd><input v-model="form.event_date" name="event_date" type="date" :aria-label="$t('vendor_quotation_form.event_date')" :class="[ink, 'font-medium @xl:text-right']"></dd>
                            <dt class="text-ink-muted">{{ $t('vendor_quotation_form.event_location') }}</dt>
                            <dd><input v-model="form.event_location" name="event_location" maxlength="255" :aria-label="$t('vendor_quotation_form.event_location')" :placeholder="$t('vendor_quotation_form.event_location_placeholder')" :class="[ink, 'font-medium @xl:text-right']"></dd>
                        </dl>
                    </section>

                    <section class="mt-8">
                        <div class="hidden grid-cols-[minmax(0,1fr)_64px_120px_110px_24px] gap-3 border-b-2 border-ink pb-2.5 text-[11px] font-semibold tracking-[0.14em] uppercase @xl:grid">
                            <span>{{ $t('vendor_quotation_form.item') }}</span>
                            <span class="text-right">{{ $t('vendor_quotation_form.qty') }}</span>
                            <span class="text-right">{{ $t('vendor_quotation_form.unit_price_short') }}</span>
                            <span class="text-right">{{ $t('vendor_quotation_form.amount') }}</span>
                            <span></span>
                        </div>
                        <div class="border-b-2 border-ink @xl:hidden"></div>

                        <p v-if="errors.items" class="mt-2 text-sm text-red-700">{{ errors.items }}</p>

                        <ol>
                            <li v-for="(item, index) in items" :key="item.key" class="grid grid-cols-[minmax(0,1fr)_24px] gap-x-3 gap-y-2 border-b border-line py-3 @xl:grid-cols-[minmax(0,1fr)_64px_120px_110px_24px] @xl:items-start">
                                <div class="flex min-w-0 flex-col gap-0.5">
                                    <template v-if="item.package_id">
                                        <input type="hidden" :name="`items[${index}][package_id]`" :value="item.package_id">
                                        <p class="flex flex-wrap items-center gap-2 px-1.5 py-1 font-semibold">
                                            {{ packageOf(item.package_id)?.name ?? item.name }}
                                            <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[10px] font-medium tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation_form.package') }}</span>
                                        </p>
                                        <p v-if="packageOf(item.package_id)?.features?.length" class="px-1.5 text-xs text-ink-muted">{{ packageOf(item.package_id).features.join(' · ') }}</p>
                                    </template>
                                    <template v-else>
                                        <input v-model="item.name" :name="`items[${index}][name]`" required maxlength="150" :aria-label="$t('vendor_quotation_form.item_name')" :placeholder="$t('vendor_quotation_form.item_name_placeholder')" :class="[ink, 'font-semibold']">
                                        <input v-model="item.description" :name="`items[${index}][description]`" maxlength="500" :aria-label="$t('vendor_quotation_form.item_description')" :placeholder="$t('vendor_quotation_form.item_description')" :class="[ink, 'text-sm text-ink-muted']">
                                        <span v-if="itemError(index, 'name')" class="text-xs text-red-700">{{ itemError(index, 'name') }}</span>
                                    </template>
                                </div>

                                <button type="button" class="flex size-6 items-center justify-center rounded-full text-ink-muted transition hover:bg-red-50 hover:text-red-700 @xl:order-last @xl:mt-1" :aria-label="$t('vendor_quotation_form.remove')" @click="remove(index)">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                                </button>

                                <div class="col-span-2 grid grid-cols-3 items-center gap-2 @xl:contents">
                                    <label class="flex flex-col @xl:block">
                                        <span class="text-[11px] text-ink-muted @xl:sr-only">{{ $t('vendor_quotation_form.qty') }}</span>
                                        <input v-model="item.quantity" :name="`items[${index}][quantity]`" type="number" min="1" step="1" required :class="[ink, 'text-right tabular-nums']">
                                    </label>
                                    <label class="flex flex-col @xl:block">
                                        <span class="text-[11px] text-ink-muted @xl:sr-only">{{ $t('vendor_quotation_form.unit_price_short') }}</span>
                                        <input v-model="item.unit_price" :name="`items[${index}][unit_price]`" type="number" min="0" step="0.01" required :class="[ink, 'text-right tabular-nums']">
                                    </label>
                                    <p class="self-end py-1 text-right font-medium whitespace-nowrap tabular-nums @xl:self-start">{{ money(lineTotal(item)) }}</p>
                                </div>
                            </li>
                        </ol>

                        <p v-if="!items.length" class="border-b border-line py-6 text-center text-sm text-ink-muted">{{ $t('vendor_quotation_form.no_items') }}</p>
                    </section>

                    <div class="mt-6 flex justify-end">
                        <dl class="flex w-full max-w-sm flex-col gap-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-ink-muted">{{ $t('vendor_quotation_form.subtotal') }}</dt>
                                <dd class="tabular-nums">{{ money(sums.subtotal) }}</dd>
                            </div>
                            <div v-if="sums.discount > 0" class="flex justify-between gap-4">
                                <dt class="text-ink-muted">{{ $t('vendor_quotation_form.discount') }}<template v-if="form.discount_type === 'percent'"> ({{ Number(form.discount_value) }}%)</template></dt>
                                <dd class="whitespace-nowrap tabular-nums">− {{ money(sums.discount) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 rounded-xl bg-ivory px-4 py-3 text-base font-semibold">
                                <dt>{{ $t('vendor_quotation_form.total') }}</dt>
                                <dd class="tabular-nums">{{ money(sums.total) }}</dd>
                            </div>
                            <template v-if="sums.deposit > 0">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-ink-muted">{{ $t('vendor_quotation_form.deposit') }}</dt>
                                    <dd class="font-medium tabular-nums">{{ money(sums.deposit) }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-ink-muted">{{ $t('vendor_quotation_form.balance') }}</dt>
                                    <dd class="tabular-nums">{{ money(sums.balance) }}</dd>
                                </div>
                            </template>
                        </dl>
                    </div>

                    <section class="mt-8">
                        <p :class="label">{{ $t('vendor_quotation_form.notes') }}</p>
                        <textarea v-model="form.notes" name="notes" rows="2" maxlength="2000" :aria-label="$t('vendor_quotation_form.notes')" :placeholder="$t('vendor_quotation_form.notes_help')" :class="[ink, 'mt-1 resize-y text-sm leading-relaxed']"></textarea>
                        <span v-if="errors.notes" class="text-xs text-red-700">{{ errors.notes }}</span>
                    </section>

                    <section class="mt-6 rounded-2xl border border-line p-4 @xl:p-5">
                        <p :class="label">{{ $t('vendor_quotation_form.terms') }}</p>
                        <textarea v-model="form.terms" name="terms" rows="5" maxlength="5000" :aria-label="$t('vendor_quotation_form.terms')" :placeholder="$t('vendor_quotation_form.terms_placeholder')" :class="[ink, 'mt-1 resize-y text-sm leading-relaxed text-ink-muted']"></textarea>
                        <span v-if="errors.terms" class="text-xs text-red-700">{{ errors.terms }}</span>
                        <label class="mt-3 flex items-center gap-2 text-xs text-ink-muted">
                            <input type="hidden" name="save_terms_as_default" value="0">
                            <input v-model="form.save_terms_as_default" type="checkbox" name="save_terms_as_default" value="1" class="size-4 rounded border-line text-brand-600">
                            <span>{{ $t('vendor_quotation_form.save_terms_as_default') }}</span>
                        </label>
                    </section>
                </div>
            </article>

            <p class="text-xs text-ink-muted">{{ $t('vendor_quotation_form.sheet_hint') }}</p>
        </div>

        <!-- The choices around it -->
        <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-24 lg:col-span-4">
            <section :class="[card, 'hidden lg:flex']">
                <div class="flex items-baseline justify-between gap-3">
                    <p :class="cardTitle">{{ $t('vendor_quotation_form.total') }}</p>
                    <p class="font-display text-2xl font-semibold tabular-nums">{{ money(sums.total) }}</p>
                </div>
                <div class="flex flex-col gap-2">
                    <button v-if="canSend" type="submit" name="send" value="1" :class="primary">{{ $t('vendor_quotation_form.save_and_send') }}</button>
                    <button type="submit" :class="canSend ? secondary : primary">{{ canSend ? $t('vendor_quotation_form.save_draft') : $t('vendor_quotation_form.save') }}</button>
                    <a :href="cancelUrl" class="rounded-full px-5 py-2 text-center text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('vendor_quotation_form.cancel') }}</a>
                </div>
                <p class="text-xs text-ink-muted">{{ $t('vendor_quotation_form.save_hint') }}</p>
            </section>

            <section :class="card">
                <p :class="cardTitle">{{ $t('vendor_quotation_form.add_items') }}</p>
                <ul v-if="packages.length" class="flex flex-col gap-2">
                    <li v-for="p in packages" :key="p.id">
                        <button type="button" class="flex w-full items-center justify-between gap-3 rounded-xl border border-line bg-surface px-3 py-2.5 text-left text-sm transition hover:border-brand-400" @click="addPackage(p)">
                            <span class="min-w-0">
                                <span class="block font-medium break-words">{{ p.name }}</span>
                                <span class="text-xs text-ink-muted tabular-nums">{{ money(p.price) }}<template v-if="timesAdded(p.id)"> · {{ $t('vendor_quotation_form.added') }}</template></span>
                            </span>
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-700" aria-hidden="true">+</span>
                        </button>
                    </li>
                </ul>
                <button type="button" class="rounded-xl border border-dashed border-line px-3 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="addAddon">+ {{ $t('vendor_quotation_form.add_addon') }}</button>
            </section>

            <section :class="card">
                <p :class="cardTitle">{{ $t('vendor_quotation_form.pricing') }}</p>

                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('vendor_quotation_form.discount') }}</span>
                    <div class="flex gap-2">
                        <span class="inline-flex shrink-0 overflow-hidden rounded-xl border border-line">
                            <button type="button" :class="segment(form.discount_type === 'fixed')" @click="form.discount_type = 'fixed'">RM</button>
                            <button type="button" :class="segment(form.discount_type === 'percent')" @click="form.discount_type = 'percent'">%</button>
                        </span>
                        <input v-model="form.discount_value" type="number" min="0" step="0.01" :aria-label="$t('vendor_quotation_form.discount')" :class="[field, 'text-right']">
                    </div>
                    <span v-if="errors.discount_value" class="text-xs text-red-700">{{ errors.discount_value }}</span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('vendor_quotation_form.deposit') }}</span>
                    <div class="flex gap-2">
                        <span class="inline-flex shrink-0 overflow-hidden rounded-xl border border-line">
                            <button type="button" :class="segment(form.deposit_type === 'fixed')" @click="form.deposit_type = 'fixed'">RM</button>
                            <button type="button" :class="segment(form.deposit_type === 'percent')" @click="form.deposit_type = 'percent'">%</button>
                        </span>
                        <input v-model="form.deposit_value" type="number" min="0" step="0.01" :aria-label="$t('vendor_quotation_form.deposit')" :class="[field, 'text-right']">
                    </div>
                    <span v-if="errors.deposit_value" class="text-xs text-red-700">{{ errors.deposit_value }}</span>
                </div>

                <dl class="flex flex-col gap-1 rounded-xl bg-surface-muted p-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ $t('vendor_quotation_form.discount') }}</dt><dd class="whitespace-nowrap tabular-nums">− {{ money(sums.discount) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ $t('vendor_quotation_form.deposit') }}</dt><dd class="tabular-nums">{{ money(sums.deposit) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ $t('vendor_quotation_form.balance') }}</dt><dd class="tabular-nums">{{ money(sums.balance) }}</dd></div>
                </dl>
            </section>
        </aside>

        <!-- Below lg the panel follows the sheet, so the save buttons stay in reach at the bottom. -->
        <div class="sticky bottom-3 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface-raised/95 px-4 py-3 shadow-sm backdrop-blur lg:hidden">
            <p class="font-semibold tabular-nums">{{ money(sums.total) }}</p>
            <div class="flex flex-wrap gap-2">
                <a :href="cancelUrl" class="rounded-full px-3 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('vendor_quotation_form.cancel') }}</a>
                <button type="submit" :class="canSend ? secondary : primary">{{ canSend ? $t('vendor_quotation_form.save_draft') : $t('vendor_quotation_form.save') }}</button>
                <button v-if="canSend" type="submit" name="send" value="1" :class="primary">{{ $t('vendor_quotation_form.save_and_send') }}</button>
            </div>
        </div>
    </form>
</template>
