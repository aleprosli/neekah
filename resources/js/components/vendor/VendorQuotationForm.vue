<script setup>
/**
 * Writing a quotation: the client, packages and add-ons, a discount, the
 * deposit and the terms. The sums shown here follow the vendor as they type;
 * the server works them out again on save (Quotation::recalculate) and never
 * takes them from this form.
 */
import { computed, ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    cancelUrl: { type: String, required: true },
    packages: { type: Array, required: true },
    depositTypes: { type: Array, required: true },
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
    save_terms_as_default: hasOld ? Boolean(props.old.save_terms_as_default) : false,
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

const packageToAdd = ref(props.packages[0]?.id ?? '');
const packageOptions = computed(() => props.packages.map((p) => ({ value: p.id, label: `${p.name} · ${money(p.price)}` })));
const packageName = (id) => props.packages.find((p) => String(p.id) === String(id))?.name;

const addPackage = () => {
    const chosen = props.packages.find((p) => String(p.id) === String(packageToAdd.value));
    if (chosen) items.value.push(line({ package_id: chosen.id, name: chosen.name, unit_price: chosen.price }));
};
const addAddon = () => items.value.push(line());
const remove = (index) => items.value.splice(index, 1);

const money = (amount) => `RM${Number(amount || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const round = (amount) => Math.round(amount * 100) / 100;

/** The same order Quotation::recalculate() works in. */
const sums = computed(() => {
    const subtotal = round(items.value.reduce((sum, item) => sum + Math.max(1, Number(item.quantity) || 1) * Math.max(0, Number(item.unit_price) || 0), 0));
    const discountValue = Math.max(0, Number(form.value.discount_value) || 0);
    const discount = round(form.value.discount_type === 'percent' ? (subtotal * Math.min(100, discountValue)) / 100 : Math.min(discountValue, subtotal));
    const total = round(subtotal - discount);
    const depositValue = Math.max(0, Number(form.value.deposit_value) || 0);
    const deposit = round(form.value.deposit_type === 'percent' ? (total * Math.min(100, depositValue)) / 100 : Math.min(depositValue, total));

    return { subtotal, discount, total, deposit, balance: round(total - deposit) };
});

const itemError = (index, field) => props.errors[`items.${index}.${field}`];
</script>

<template>
    <form :action="action" method="POST" class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">
        <input v-if="quotation.enquiry_id" type="hidden" name="enquiry_id" :value="quotation.enquiry_id">

        <div class="flex min-w-0 flex-col gap-6">
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation_form.client') }}</p>
                <UiField v-model="form.client_name" :label="$t('vendor_quotation_form.client_name')" name="client_name" :error="errors.client_name" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <UiField v-model="form.client_phone" :label="$t('vendor_quotation_form.client_phone')" name="client_phone" type="tel" :error="errors.client_phone" />
                    <UiField v-model="form.client_email" :label="$t('vendor_quotation_form.client_email')" name="client_email" type="email" :help="$t('vendor_quotation_form.client_email_help')" :error="errors.client_email" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UiField v-model="form.event_date" :label="$t('vendor_quotation_form.event_date')" name="event_date" type="date" :error="errors.event_date" />
                    <UiField v-model="form.event_location" :label="$t('vendor_quotation_form.event_location')" name="event_location" :error="errors.event_location" />
                </div>
            </section>

            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation_form.items') }}</p>
                <p v-if="errors.items" class="text-sm text-red-700">{{ errors.items }}</p>

                <ol class="flex flex-col gap-3">
                    <li v-for="(item, index) in items" :key="item.key" class="flex min-w-0 flex-col gap-3 rounded-xl border border-line p-4">
                        <input v-if="item.package_id" type="hidden" :name="`items[${index}][package_id]`" :value="item.package_id">
                        <div class="flex items-start justify-between gap-3">
                            <span class="rounded-full bg-surface-muted px-2.5 py-0.5 text-[11px] font-medium text-ink-muted">{{ item.package_id ? $t('vendor_quotation_form.package') : $t('vendor_quotation_form.addon') }}</span>
                            <button type="button" class="text-xs font-medium text-ink-muted underline-offset-4 hover:text-red-700 hover:underline" @click="remove(index)">{{ $t('vendor_quotation_form.remove') }}</button>
                        </div>

                        <p v-if="item.package_id" class="font-semibold">{{ packageName(item.package_id) ?? item.name }}</p>
                        <template v-else>
                            <UiField v-model="item.name" :label="$t('vendor_quotation_form.item_name')" :name="`items[${index}][name]`" :placeholder="$t('vendor_quotation_form.item_name_placeholder')" :error="itemError(index, 'name')" required />
                            <UiField v-model="item.description" :label="$t('vendor_quotation_form.item_description')" :name="`items[${index}][description]`" :error="itemError(index, 'description')" />
                        </template>

                        <div class="grid grid-cols-[minmax(0,0.6fr)_minmax(0,1fr)] gap-3 sm:grid-cols-[120px_minmax(0,200px)_1fr] sm:items-end">
                            <UiField v-model="item.quantity" :label="$t('vendor_quotation_form.quantity')" :name="`items[${index}][quantity]`" type="number" min="1" step="1" :error="itemError(index, 'quantity')" required />
                            <UiField v-model="item.unit_price" :label="$t('vendor_quotation_form.unit_price')" :name="`items[${index}][unit_price]`" type="number" min="0" step="0.01" :error="itemError(index, 'unit_price')" required />
                            <p class="col-span-2 text-right text-sm font-semibold tabular-nums sm:col-span-1 sm:pb-3">{{ money(Math.max(1, Number(item.quantity) || 1) * Math.max(0, Number(item.unit_price) || 0)) }}</p>
                        </div>
                    </li>
                </ol>

                <p v-if="!items.length" class="rounded-xl border border-dashed border-line p-4 text-sm text-ink-muted">{{ $t('vendor_quotation_form.no_items') }}</p>

                <div class="flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-end">
                    <div v-if="packages.length" class="min-w-0 flex-1">
                        <UiSelect v-model="packageToAdd" :label="$t('vendor_quotation_form.add_package_label')" name="" :options="packageOptions" />
                    </div>
                    <button v-if="packages.length" type="button" class="rounded-full border border-line px-4 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="addPackage">+ {{ $t('vendor_quotation_form.add_package') }}</button>
                    <button type="button" class="rounded-full border border-line px-4 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="addAddon">+ {{ $t('vendor_quotation_form.add_addon') }}</button>
                </div>
            </section>

            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_quotation_form.terms_section') }}</p>
                <UiField v-model="form.valid_until" :label="$t('vendor_quotation_form.valid_until')" name="valid_until" type="date" :error="errors.valid_until" required />
                <UiTextarea v-model="form.terms" :label="$t('vendor_quotation_form.terms')" name="terms" :rows="6" :placeholder="$t('vendor_quotation_form.terms_placeholder')" :error="errors.terms" />
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="save_terms_as_default" value="0">
                    <input v-model="form.save_terms_as_default" type="checkbox" name="save_terms_as_default" value="1" class="size-4 rounded border-line text-brand-600">
                    <span>{{ $t('vendor_quotation_form.save_terms_as_default') }}</span>
                </label>
                <UiTextarea v-model="form.notes" :label="$t('vendor_quotation_form.notes')" name="notes" :rows="3" :help="$t('vendor_quotation_form.notes_help')" :error="errors.notes" />
            </section>
        </div>

        <aside class="flex min-w-0 flex-col gap-4 lg:sticky lg:top-6 lg:self-start">
            <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-3">
                    <UiSelect v-model="form.discount_type" :label="$t('vendor_quotation_form.discount')" name="discount_type" :options="depositTypes" />
                    <UiField v-model="form.discount_value" :label="form.discount_type === 'percent' ? '%' : 'RM'" name="discount_value" type="number" min="0" step="0.01" :error="errors.discount_value" />
                </div>
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-3">
                    <UiSelect v-model="form.deposit_type" :label="$t('vendor_quotation_form.deposit')" name="deposit_type" :options="depositTypes" />
                    <UiField v-model="form.deposit_value" :label="form.deposit_type === 'percent' ? '%' : 'RM'" name="deposit_value" type="number" min="0" step="0.01" :error="errors.deposit_value" />
                </div>

                <dl class="flex flex-col gap-2 rounded-xl bg-surface-muted p-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ $t('vendor_quotation_form.subtotal') }}</dt><dd class="tabular-nums">{{ money(sums.subtotal) }}</dd></div>
                    <div v-if="sums.discount > 0" class="flex justify-between gap-3"><dt class="text-ink-muted">{{ $t('vendor_quotation_form.discount') }}</dt><dd class="tabular-nums">− {{ money(sums.discount) }}</dd></div>
                    <div class="flex justify-between gap-3 border-t border-line pt-2 font-semibold"><dt>{{ $t('vendor_quotation_form.total') }}</dt><dd class="tabular-nums">{{ money(sums.total) }}</dd></div>
                    <template v-if="sums.deposit > 0">
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ $t('vendor_quotation_form.deposit') }}</dt><dd class="tabular-nums">{{ money(sums.deposit) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ $t('vendor_quotation_form.balance') }}</dt><dd class="tabular-nums">{{ money(sums.balance) }}</dd></div>
                    </template>
                </dl>
            </section>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('vendor_quotation_form.save') }}</button>
                <a :href="cancelUrl" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('vendor_quotation_form.cancel') }}</a>
            </div>
            <p class="text-xs text-ink-muted">{{ $t('vendor_quotation_form.save_hint') }}</p>
        </aside>
    </form>
</template>
