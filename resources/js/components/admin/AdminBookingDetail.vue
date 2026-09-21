<script setup>
/** One booking as the platform sees it: both sides, and where the money sits. */
defineProps({
    booking: { type: Object, required: true },
});
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
        <dl class="grid min-w-0 gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2 [&>div]:min-w-0">
            <div>
                <dt class="text-ink-muted">{{ $t('admin_booking.vendor') }}</dt>
                <dd class="font-semibold"><a :href="booking.vendor.url" class="hover:text-brand-700">{{ booking.vendor.name }}</a></dd>
                <dd class="text-ink-muted">{{ booking.vendor.category }}</dd>
            </div>

            <div>
                <dt class="text-ink-muted">{{ $t('admin_booking.pengantin') }}</dt>
                <dd class="font-semibold">{{ booking.customer.name }}</dd>
                <dd class="break-words text-ink-muted">{{ booking.customer.email }}</dd>
            </div>

            <div><dt class="text-ink-muted">{{ $t('admin_booking.pakej') }}</dt><dd class="font-semibold">{{ booking.package_name }}</dd></div>
            <div><dt class="text-ink-muted">{{ $t('admin_booking.dibuat') }}</dt><dd class="font-semibold">{{ booking.created_at }}</dd></div>
            <div v-if="booking.wedding"><dt class="text-ink-muted">{{ $t('admin_booking.majlis') }}</dt><dd class="font-semibold">{{ booking.wedding }}</dd></div>

            <div v-if="booking.notes" class="sm:col-span-2"><dt class="text-ink-muted">{{ $t('admin_booking.nota') }}</dt><dd>{{ booking.notes }}</dd></div>

            <div v-if="booking.review" class="sm:col-span-2">
                <dt class="text-ink-muted">{{ $t('admin_booking.review') }}</dt>
                <dd class="text-gold-500">{{ '★'.repeat(booking.review.rating) }}</dd>
                <dd>{{ booking.review.comment }}</dd>
            </div>
        </dl>

        <aside class="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('admin_booking.kewangan') }}</p>
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
                <div class="flex justify-between"><dt class="text-ink-muted">{{ $t('admin_booking.dibayar') }}</dt><dd>{{ booking.paid }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Komisen ({{ booking.commission_rate }}%)</dt><dd>{{ booking.commission }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>{{ $t('admin_booking.payout_vendor') }}</dt><dd>{{ booking.payout }}</dd></div>
            </dl>
        </aside>
    </div>
</template>
