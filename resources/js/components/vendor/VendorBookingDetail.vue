<script setup>
/** One booking as the vendor sees it: who, when, what they are owed. */
defineProps({
    booking: { type: Object, required: true },
    timeline: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="flex flex-col gap-6">
            <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-ink-muted">Pelanggan</dt>
                    <dd class="font-semibold">{{ booking.customer.name }}</dd>
                    <dd class="break-words text-ink-muted">{{ booking.customer.contact }}</dd>
                </div>
                <div><dt class="text-ink-muted">Pakej</dt><dd class="font-semibold">{{ booking.package_name }}</dd></div>
                <div><dt class="text-ink-muted">Tarikh majlis</dt><dd class="font-semibold">{{ booking.event_date }}</dd></div>
                <div><dt class="text-ink-muted">Dibuat</dt><dd class="font-semibold">{{ booking.created_at }}</dd></div>
                <div v-if="booking.notes" class="sm:col-span-2"><dt class="text-ink-muted">Nota</dt><dd>{{ booking.notes }}</dd></div>
            </dl>

            <section v-if="timeline.length" class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Slot anda pada hari majlis</p>
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
                <p class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">Anda hanya melihat slot yang ditugaskan kepada anda. Pengantin menguruskan timeline penuh.</p>
            </section>

            <div v-if="booking.review" class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Review pelanggan</p>
                <p class="mt-2 text-gold-500">{{ '★'.repeat(booking.review.rating) }}</p>
                <p class="mt-1 text-sm">{{ booking.review.comment }}</p>
            </div>
        </div>

        <aside class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Pembayaran</p>
            <p class="font-display text-2xl font-semibold">{{ booking.total }}</p>

            <ul class="flex flex-col gap-2 text-sm">
                <li v-for="payment in booking.payments" :key="payment.label" class="flex items-center justify-between rounded-xl border border-line px-3 py-2">
                    <span>{{ payment.label }}</span>
                    <span class="text-right">
                        <span class="font-medium">{{ payment.amount }}</span>
                        <span :class="['block text-xs', payment.is_paid ? 'text-emerald-600' : 'text-ink-muted']">{{ payment.status }}</span>
                    </span>
                </li>
            </ul>

            <dl class="flex flex-col gap-1 border-t border-line pt-3 text-sm">
                <div class="flex justify-between text-ink-muted"><dt>Komisen platform ({{ booking.commission_rate }}%)</dt><dd>- {{ booking.commission }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Anda terima</dt><dd>{{ booking.payout }}</dd></div>
            </dl>
        </aside>
    </div>
</template>
