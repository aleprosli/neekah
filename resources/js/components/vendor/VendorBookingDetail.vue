<script setup>
/**
 * One booking as the vendor sees it: who, when, what they are owed, and the
 * payments the couple says they have made.
 *
 * Nothing the couple records counts until the vendor confirms it here, because
 * the vendor is the only one who can see their own account.
 */
import UiConfirm from '../ui/UiConfirm.vue';

defineProps({
    booking: { type: Object, required: true },
    timeline: { type: Array, default: () => [] },
    csrf: { type: String, required: true },
});

const tones = {
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    sky: 'bg-sky-50 text-sky-700',
    muted: 'bg-surface-muted text-ink-muted',
};
</script>

<template>
    <!-- min-w-0 and break-words together keep one long email or location from
         widening the page past a phone's screen. -->
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="flex min-w-0 flex-col gap-6">
            <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2 [&>div]:min-w-0">
                <div>
                    <dt class="text-ink-muted">{{ $t('vendor_booking.pelanggan') }}</dt>
                    <dd class="font-semibold">{{ booking.customer.name }}</dd>
                    <dd class="break-words text-ink-muted">{{ booking.customer.contact }}</dd>
                </div>
                <div><dt class="text-ink-muted">{{ $t('vendor_booking.pakej') }}</dt><dd class="font-semibold">{{ booking.package_name }}</dd></div>
                <div><dt class="text-ink-muted">{{ $t('vendor_booking.tarikh_majlis') }}</dt><dd class="font-semibold">{{ booking.event_date }}</dd></div>
                <div><dt class="text-ink-muted">{{ $t('vendor_booking.dibuat') }}</dt><dd class="font-semibold">{{ booking.created_at }}</dd></div>
                <div v-if="booking.notes" class="sm:col-span-2"><dt class="text-ink-muted">{{ $t('vendor_booking.nota') }}</dt><dd>{{ booking.notes }}</dd></div>
            </dl>

            <section v-if="timeline.length" class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_booking.slot_anda_pada_hari_majlis') }}</p>
                <ol class="mt-3 flex flex-col gap-3">
                    <li v-for="item in timeline" :key="item.id" class="flex gap-4 text-sm">
                        <span class="w-24 shrink-0 font-display font-semibold">{{ item.time }}</span>
                        <div class="min-w-0">
                            <p class="font-medium">{{ item.title }}</p>
                            <p v-if="item.location" class="text-xs text-ink-muted">📍 {{ item.location }}</p>
                            <p v-if="item.notes" class="text-xs text-ink-muted">{{ item.notes }}</p>
                        </div>
                    </li>
                </ol>
                <p class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">{{ $t('vendor_booking.anda_hanya_melihat_slot_yang') }}</p>
            </section>

            <div v-if="booking.review" class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_booking.review_pelanggan') }}</p>
                <p class="mt-2 text-gold-500">{{ '★'.repeat(booking.review.rating) }}</p>
                <p class="mt-1 text-sm">{{ booking.review.comment }}</p>
            </div>
        </div>

        <aside class="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_booking.pembayaran') }}</p>
            <p class="font-display text-2xl font-semibold">{{ booking.total }}</p>

            <ul v-if="booking.payments.length" class="flex flex-col gap-3 text-sm">
                <li v-for="payment in booking.payments" :key="payment.reference" class="flex flex-col gap-2 rounded-xl border border-line p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold">{{ payment.amount }}</span>
                        <span :class="['rounded-full px-2.5 py-0.5 text-[11px] font-medium', tones[payment.status_tone] || tones.muted]">{{ payment.status_label }}</span>
                    </div>

                    <p class="text-xs text-ink-muted">{{ payment.paid_on }} · direkod oleh {{ payment.recorded_by }}</p>
                    <p v-if="payment.note" class="text-xs break-words text-ink-muted">{{ payment.note }}</p>

                    <a v-if="payment.receipt_url" :href="payment.receipt_url" target="_blank" rel="noopener" class="text-xs font-medium text-brand-700 underline underline-offset-4">{{ $t('vendor_booking.lihat_resit') }}</a>
                    <a v-if="payment.document_url" :href="payment.document_url" target="_blank" rel="noopener" class="text-xs font-medium text-brand-700 underline underline-offset-4">{{ $t('payments.official_receipt') }}</a>

                    <div v-if="payment.awaiting" class="flex flex-wrap gap-2 pt-1">
                        <UiConfirm
                            :action="payment.verify_url"
                            :title="$t('vendor_booking.sahkan_bayaran_ini_diterima')"
                            :message="$t('vendor_booking.pastikan_bayaran_masuk', { amount: payment.amount })"
                            :confirm-label="$t('vendor_booking.ya_saya_telah_terima')"
                            trigger-class="rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                            :csrf="csrf"
                        >{{ $t('vendor_booking.sahkan_diterima') }}</UiConfirm>

                        <UiConfirm
                            :action="payment.reject_url"
                            method="DELETE"
                            :title="$t('vendor_booking.tanda_bayaran_ini_tidak_diterima')"
                            :message="$t('vendor_booking.pengantin_akan_diminta_menyemak_resit')"
                            :confirm-label="$t('vendor_booking.ya_saya_tidak_jumpa')"
                            tone="danger"
                            :csrf="csrf"
                        >{{ $t('vendor_booking.tidak_diterima') }}</UiConfirm>
                    </div>
                </li>
            </ul>

            <p v-else class="rounded-xl bg-surface-muted p-3 text-xs text-ink-muted">{{ $t('vendor_booking.pengantin_belum_merekodkan_sebarang_bayaran') }}</p>

            <dl class="flex flex-col gap-1 border-t border-line pt-3 text-sm">
                <div class="flex justify-between text-ink-muted"><dt>{{ $t('vendor_booking.belum_disahkan') }}</dt><dd>{{ booking.outstanding }}</dd></div>
                <!-- Only bookings made under a commission show one; while Neekah is free, the vendor keeps it all. -->
                <div v-if="booking.has_commission" class="flex justify-between text-ink-muted"><dt>Komisen platform ({{ booking.commission_rate }}%)</dt><dd>- {{ booking.commission }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>{{ $t('vendor_booking.anda_terima') }}</dt><dd>{{ booking.payout }}</dd></div>
            </dl>
        </aside>
    </div>
</template>
