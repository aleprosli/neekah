<script setup>
/**
 * Recording a booking agreed off-platform. The split is shown as the vendor
 * picks a package, so the payout is never a surprise after the fact.
 */
import { computed, ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    createPackageUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    packages: { type: Array, required: true },
    depositRate: { type: Number, required: true },
    commissionRate: { type: Number, required: true },
    errors: { type: Object, default: () => ({}) },
    old: { type: Object, default: () => ({}) },
});

const form = ref({
    customer_email: props.old.customer_email ?? '',
    package_id: props.old.package_id ?? props.packages[0]?.id ?? '',
    event_date: props.old.event_date ?? '',
    notes: props.old.notes ?? '',
});

const money = (amount) => `RM${Number(amount).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const options = computed(() => props.packages.map((p) => ({ value: p.id, label: `${p.name} · ${money(p.price)}` })));
const chosen = computed(() => props.packages.find((p) => String(p.id) === String(form.value.package_id)));

const split = computed(() => {
    if (!chosen.value) return null;

    const total = Number(chosen.value.price);
    const deposit = total * props.depositRate;
    const commission = total * props.commissionRate;

    return {
        total: money(total),
        deposit: money(deposit),
        balance: money(total - deposit),
        commission: money(commission),
        payout: money(total - commission),
    };
});
</script>

<template>
    <p v-if="!packages.length" class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">
        Anda perlu <a :href="createPackageUrl" class="font-medium text-brand-600 underline underline-offset-4">tambah pakej aktif</a> sebelum merekod booking.
    </p>

    <form v-else :action="action" method="POST" class="flex max-w-2xl flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
        <input type="hidden" name="_token" :value="csrf">

        <UiField
            v-model="form.customer_email"
            label="Emel pelanggan"
            name="customer_email"
            type="email"
            placeholder="aina@contoh.com"
            :error="errors.customer_email"
            help="Pelanggan mesti sudah mendaftar akaun pengantin di Neekah."
            required
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <UiSelect v-model="form.package_id" label="Pakej" name="package_id" :options="options" :error="errors.package_id" required />
            <UiField v-model="form.event_date" label="Tarikh majlis" name="event_date" type="date" :error="errors.event_date" required />
        </div>

        <UiTextarea v-model="form.notes" label="Nota (pilihan)" name="notes" :rows="3" placeholder="Lokasi, jumlah tetamu, permintaan khas" :error="errors.notes" />

        <dl v-if="split" class="flex flex-col gap-2 rounded-xl bg-surface-muted p-4 text-sm">
            <div class="flex justify-between"><dt class="text-ink-muted">Jumlah pakej</dt><dd class="font-medium">{{ split.total }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-muted">Deposit ({{ Math.round(depositRate * 100) }}%)</dt><dd>{{ split.deposit }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-muted">Baki sebelum majlis</dt><dd>{{ split.balance }}</dd></div>
            <div class="flex justify-between"><dt class="text-ink-muted">Komisen platform ({{ Math.round(commissionRate * 100) }}%)</dt><dd>− {{ split.commission }}</dd></div>
            <div class="flex justify-between border-t border-line pt-2 font-semibold"><dt>Anda terima</dt><dd>{{ split.payout }}</dd></div>
        </dl>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Rekod booking</button>
            <a :href="cancelUrl" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
        </div>
    </form>
</template>
