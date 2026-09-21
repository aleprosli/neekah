<script setup>
/**
 * Recording a booking agreed off-platform. The payout is shown as the vendor
 * picks a package, so what they keep is never a surprise after the fact.
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
    const commission = total * props.commissionRate;

    return {
        total: money(total),
        commission: money(commission),
        payout: money(total - commission),
    };
});
</script>

<template>
    <p v-if="!packages.length" class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">{{ $t('vendor_booking_form.anda_perlu') }} <a :href="createPackageUrl" class="font-medium text-brand-600 underline underline-offset-4">{{ $t('vendor_booking_form.tambah_pakej_aktif') }}</a>{{ $t('vendor_booking_form.sebelum_merekod_booking') }}
    </p>

    <form v-else :action="action" method="POST" class="flex max-w-2xl flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
        <input type="hidden" name="_token" :value="csrf">

        <UiField
            v-model="form.customer_email"
            :label="$t('vendor_booking_form.emel_pelanggan')"
            name="customer_email"
            type="email"
            placeholder="aina@contoh.com"
            :error="errors.customer_email"
            :help="$t('vendor_booking_form.pelanggan_mesti_sudah_mendaftar_akaun')"
            required
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <UiSelect v-model="form.package_id" :label="$t('vendor_booking_form.pakej')" name="package_id" :options="options" :error="errors.package_id" required />
            <UiField v-model="form.event_date" :label="$t('vendor_booking_form.tarikh_majlis')" name="event_date" type="date" :error="errors.event_date" required />
        </div>

        <UiTextarea v-model="form.notes" :label="$t('vendor_booking_form.nota_pilihan')" name="notes" :rows="3" :placeholder="$t('vendor_booking_form.lokasi_jumlah_tetamu_permintaan_khas')" :error="errors.notes" />

        <dl v-if="split" class="flex flex-col gap-2 rounded-xl bg-surface-muted p-4 text-sm">
            <div class="flex justify-between"><dt class="text-ink-muted">{{ $t('vendor_booking_form.jumlah_pakej') }}</dt><dd class="font-medium">{{ split.total }}</dd></div>
            <!-- While Neekah is free the rate is 0, and a "Komisen 0%" line would only raise a question. -->
            <template v-if="commissionRate > 0">
                <div class="flex justify-between"><dt class="text-ink-muted">Komisen platform ({{ Math.round(commissionRate * 100) }}%)</dt><dd>− {{ split.commission }}</dd></div>
                <div class="flex justify-between border-t border-line pt-2 font-semibold"><dt>{{ $t('vendor_booking_form.anda_terima') }}</dt><dd>{{ split.payout }}</dd></div>
            </template>
            <p v-else class="border-t border-line pt-2 text-xs text-ink-muted">{{ $t('vendor_booking_form.tiada_komisen_platform_anda_terima') }}</p>
        </dl>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('vendor_booking_form.rekod_booking') }}</button>
            <a :href="cancelUrl" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('vendor_booking_form.batal') }}</a>
        </div>
    </form>
</template>
