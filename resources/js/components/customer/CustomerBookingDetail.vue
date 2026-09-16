<script setup>
/**
 * One booking as the couple sees it: where it is up to, what is owed, and —
 * once the day has passed — the review only they can write.
 */
import { ref } from 'vue';

const props = defineProps({
    booking: { type: Object, required: true },
    steps: { type: Array, required: true },
    payments: { type: Array, required: true },
    review: { type: Object, default: null },
    reviewForm: { type: Object, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const scores = ref(
    props.reviewForm
        ? Object.fromEntries(props.reviewForm.fields.map((field) => [field.name, field.value]))
        : {},
);

const comment = ref(props.reviewForm?.comment ?? '');
const stars = (rating) => '★'.repeat(rating);
</script>

<template>
    <p v-if="errors.payment" class="mb-6 rounded-2xl bg-brand-50 px-5 py-4 text-sm text-brand-800">{{ errors.payment }}</p>

    <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
        <div class="flex flex-col gap-6">
            <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-muted">Rujukan</dt><dd class="font-semibold">{{ booking.reference }}</dd></div>
                <div><dt class="text-ink-muted">Tarikh majlis</dt><dd class="font-semibold">{{ booking.event_date }}</dd></div>
                <div><dt class="text-ink-muted">Pakej</dt><dd class="font-semibold">{{ booking.package_name }}</dd></div>
                <div><dt class="text-ink-muted">Dibuat pada</dt><dd class="font-semibold">{{ booking.created_at }}</dd></div>
                <div v-if="booking.notes" class="sm:col-span-2"><dt class="text-ink-muted">Nota</dt><dd>{{ booking.notes }}</dd></div>
            </dl>

            <ol class="flex flex-col gap-3 rounded-2xl border border-line p-5 text-sm">
                <li v-for="(step, at) in steps" :key="step.label" class="flex items-center gap-3">
                    <span :class="['flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold', step.done ? 'bg-emerald-500 text-white' : 'border border-line text-ink-muted']">
                        {{ step.done ? '✓' : at + 1 }}
                    </span>
                    <span :class="step.done ? 'font-medium' : 'text-ink-muted'">{{ step.label }}</span>
                    <span v-if="step.done && step.at" class="ml-auto text-xs text-ink-muted">{{ step.at }}</span>
                </li>
            </ol>

            <section v-if="review" class="rounded-2xl border border-line p-5">
                <h2 class="font-display text-lg font-semibold">Review anda</h2>
                <p class="mt-2 text-gold-500">{{ stars(review.rating) }}<span class="text-line">{{ stars(5 - review.rating) }}</span></p>
                <p class="mt-2 text-sm leading-relaxed">{{ review.comment }}</p>
            </section>

            <section v-else-if="reviewForm" class="flex flex-col gap-4 rounded-2xl border border-brand-200 bg-brand-50/50 p-5">
                <div>
                    <h2 class="font-display text-lg font-semibold">Beri review</h2>
                    <p class="text-sm text-ink-muted">Majlis anda telah selesai. Kongsi pengalaman anda dengan pengantin lain.</p>
                </div>

                <form :action="reviewForm.action" method="POST" class="flex flex-col gap-4">
                    <input type="hidden" name="_token" :value="csrf">

                    <div v-for="field in reviewForm.fields" :key="field.name" class="flex flex-wrap items-center justify-between gap-2">
                        <span :class="['text-sm', field.name === 'rating' ? 'font-semibold' : '']">{{ field.label }}</span>

                        <div class="flex gap-1">
                            <label v-for="score in 5" :key="score" class="cursor-pointer">
                                <input v-model="scores[field.name]" type="radio" :name="field.name" :value="score" class="peer sr-only" required>
                                <span class="flex size-9 items-center justify-center rounded-lg border border-line text-sm transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white">{{ score }}</span>
                            </label>
                        </div>
                    </div>

                    <textarea
                        v-model="comment"
                        name="comment"
                        rows="4"
                        required
                        placeholder="Ceritakan pengalaman anda dengan vendor ini…"
                        class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none"
                    ></textarea>
                    <span v-if="errors.comment" class="text-xs text-brand-700">{{ errors.comment }}</span>

                    <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Hantar review</button>
                </form>
            </section>
        </div>

        <aside class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 shadow-xl shadow-brand-900/5 lg:sticky lg:top-28 lg:self-start">
            <div>
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Jumlah</p>
                <p class="font-display text-3xl font-semibold">{{ booking.total }}</p>
                <p class="text-sm text-ink-muted">Dibayar {{ booking.paid }}</p>
            </div>

            <ul class="flex flex-col gap-3">
                <li v-for="payment in payments" :key="payment.label" class="flex flex-col gap-2 rounded-xl border border-line p-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium">{{ payment.label }}</span>
                        <span class="font-semibold">{{ payment.amount }}</span>
                    </div>

                    <p v-if="payment.note" :class="['text-xs break-words', payment.paid ? 'text-emerald-700' : 'text-ink-muted']">{{ payment.note }}</p>

                    <template v-if="payment.pay_url">
                        <form :action="payment.pay_url" method="POST">
                            <input type="hidden" name="_token" :value="csrf">
                            <button type="submit" class="w-full rounded-full bg-brand-600 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ payment.pay_label }}</button>
                        </form>
                        <p class="text-center text-[11px] text-ink-muted">Sandbox: bayaran diluluskan serta-merta.</p>
                    </template>
                </li>
            </ul>

            <p class="text-xs text-ink-muted">Komisen platform {{ booking.commission_rate }}% ditolak daripada pembayaran kepada vendor.</p>
        </aside>
    </div>
</template>
