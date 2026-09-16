<script setup>
/** One booking as the platform sees it: both sides, and where the money sits. */
defineProps({
    booking: { type: Object, required: true },
});
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-ink-muted">Vendor</dt>
                <dd class="font-semibold"><a :href="booking.vendor.url" class="hover:text-brand-700">{{ booking.vendor.name }}</a></dd>
                <dd class="text-ink-muted">{{ booking.vendor.category }}</dd>
            </div>

            <div>
                <dt class="text-ink-muted">Pengantin</dt>
                <dd class="font-semibold">{{ booking.customer.name }}</dd>
                <dd class="break-words text-ink-muted">{{ booking.customer.email }}</dd>
            </div>

            <div><dt class="text-ink-muted">Pakej</dt><dd class="font-semibold">{{ booking.package_name }}</dd></div>
            <div><dt class="text-ink-muted">Dibuat</dt><dd class="font-semibold">{{ booking.created_at }}</dd></div>
            <div v-if="booking.wedding"><dt class="text-ink-muted">Majlis</dt><dd class="font-semibold">{{ booking.wedding }}</dd></div>

            <div v-if="booking.notes" class="sm:col-span-2"><dt class="text-ink-muted">Nota</dt><dd>{{ booking.notes }}</dd></div>

            <div v-if="booking.review" class="sm:col-span-2">
                <dt class="text-ink-muted">Review</dt>
                <dd class="text-gold-500">{{ '★'.repeat(booking.review.rating) }}</dd>
                <dd>{{ booking.review.comment }}</dd>
            </div>
        </dl>

        <aside class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Kewangan</p>
            <p class="font-display text-2xl font-semibold">{{ booking.total }}</p>

            <ul class="flex flex-col gap-2 text-sm">
                <li v-for="payment in booking.payments" :key="payment.reference" class="flex items-center justify-between gap-3 rounded-xl border border-line px-3 py-2">
                    <span class="min-w-0">
                        {{ payment.label }}
                        <span class="block truncate text-xs text-ink-muted">{{ payment.reference }}</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="font-medium">{{ payment.amount }}</span>
                        <span :class="['block text-xs', payment.is_paid ? 'text-emerald-600' : 'text-ink-muted']">{{ payment.status }}</span>
                    </span>
                </li>
            </ul>

            <dl class="flex flex-col gap-1 border-t border-line pt-3 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Dibayar</dt><dd>{{ booking.paid }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Komisen ({{ booking.commission_rate }}%)</dt><dd>{{ booking.commission }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Payout vendor</dt><dd>{{ booking.payout }}</dd></div>
            </dl>
        </aside>
    </div>
</template>
