<script setup>
/**
 * One booking as the couple sees it: where it is up to, what they have paid the
 * vendor directly, and — once the day has passed — the review only they can
 * write.
 *
 * No money moves here. The couple deals with the vendor and writes down what
 * changed hands; the vendor confirms it against their own account.
 */
import { ref } from 'vue';
import { useUploadForm } from '../../composables/useUploadForm.js';
import UiConfirm from '../ui/UiConfirm.vue';
import UiConfirmSubmit from '../ui/UiConfirmSubmit.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';

const props = defineProps({
    booking: { type: Object, required: true },
    steps: { type: Array, required: true },
    payments: { type: Array, required: true },
    paymentForm: { type: Object, default: null },
    cancelForm: { type: Object, default: null },
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

const { uploading, percent, error: uploadError, submit: submitUpload } = useUploadForm();

const amount = ref(props.paymentForm?.old.amount ?? '');
const receiptName = ref('');

const money = (value) =>
    'RM' + Number(value || 0).toLocaleString('ms-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const tones = {
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    sky: 'bg-sky-50 text-sky-700',
    muted: 'bg-surface-muted text-ink-muted',
};
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
                <div v-if="booking.notes" class="sm:col-span-2"><dt class="text-ink-muted">Nota</dt><dd class="whitespace-pre-line">{{ booking.notes }}</dd></div>
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

            <section v-if="paymentForm" class="flex flex-col gap-4 rounded-2xl border border-line p-5">
                <div>
                    <h2 class="font-display text-lg font-semibold">Rekod bayaran</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ paymentForm.instructions }}</p>
                </div>

                <dl v-if="paymentForm.bank" class="grid gap-2 rounded-xl bg-surface-muted p-4 text-sm sm:grid-cols-3">
                    <div v-if="paymentForm.bank.bank"><dt class="text-xs text-ink-muted">Bank</dt><dd class="font-medium">{{ paymentForm.bank.bank }}</dd></div>
                    <div v-if="paymentForm.bank.holder"><dt class="text-xs text-ink-muted">Nama akaun</dt><dd class="font-medium">{{ paymentForm.bank.holder }}</dd></div>
                    <div v-if="paymentForm.bank.number"><dt class="text-xs text-ink-muted">Nombor akaun</dt><dd class="font-mono font-medium">{{ paymentForm.bank.number }}</dd></div>
                </dl>

                <form :action="paymentForm.action" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4" @submit="submitUpload">
                    <input type="hidden" name="_token" :value="csrf">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex flex-col gap-1.5 text-sm">
                            <span class="font-medium">Jumlah dibayar (RM)</span>
                            <input
                                v-model="amount"
                                type="number"
                                name="amount"
                                step="0.01"
                                min="1"
                                required
                                :placeholder="paymentForm.outstanding > 0 ? String(paymentForm.outstanding) : '0.00'"
                                class="rounded-xl border border-line bg-surface px-4 py-2.5 focus:border-brand-400 focus:outline-none"
                            >
                            <span class="text-xs text-ink-muted">Belum direkod: {{ paymentForm.outstandingLabel }}</span>
                            <span v-if="errors.amount" class="text-xs text-brand-700">{{ errors.amount }}</span>
                        </label>

                        <label class="flex flex-col gap-1.5 text-sm">
                            <span class="font-medium">Tarikh bayaran</span>
                            <input
                                type="date"
                                name="paid_on"
                                required
                                :max="paymentForm.today"
                                :value="paymentForm.old.paid_on"
                                class="rounded-xl border border-line bg-surface px-4 py-2.5 focus:border-brand-400 focus:outline-none"
                            >
                            <span v-if="errors.paid_on" class="text-xs text-brand-700">{{ errors.paid_on }}</span>
                        </label>
                    </div>

                    <label class="flex flex-col gap-1.5 text-sm">
                        <span class="font-medium">Nota <span class="font-normal text-ink-muted">(pilihan)</span></span>
                        <input
                            type="text"
                            name="note"
                            maxlength="160"
                            :value="paymentForm.old.note"
                            placeholder="Contoh: bayaran pendahuluan, transfer Maybank2u"
                            class="rounded-xl border border-line bg-surface px-4 py-2.5 focus:border-brand-400 focus:outline-none"
                        >
                        <span v-if="errors.note" class="text-xs text-brand-700">{{ errors.note }}</span>
                    </label>

                    <label class="flex flex-col gap-1.5 text-sm">
                        <span class="font-medium">Gambar resit <span class="font-normal text-ink-muted">(pilihan)</span></span>
                        <input
                            type="file"
                            name="receipt"
                            accept="image/jpeg,image/png,image/webp"
                            class="rounded-xl border border-dashed border-line bg-surface px-4 py-3 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-600 file:px-4 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                            @change="receiptName = $event.target.files[0]?.name ?? ''"
                        >
                        <span class="text-xs text-ink-muted">{{ paymentForm.imageHint }}</span>
                        <span v-if="errors.receipt" class="text-xs text-brand-700">{{ errors.receipt }}</span>
                    </label>

                    <UiUploadProgress :uploading="uploading" :percent="percent" :error="uploadError" label="Memuat naik resit" />

                    <UiConfirmSubmit
                        title="Rekod bayaran ini?"
                        :message="`${money(amount)} akan dihantar kepada vendor untuk disahkan${receiptName ? ` bersama resit ${receiptName}` : ''}. Vendor akan menyemak akaun mereka sebelum booking disahkan.`"
                        confirm-label="Ya, rekodkan"
                        button-class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60"
                        :disabled="uploading"
                    >Rekod bayaran</UiConfirmSubmit>
                </form>
            </section>

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

                    <UiConfirmSubmit
                        title="Hantar review ini?"
                        message="Review dipaparkan di halaman awam vendor dan tidak boleh diubah selepas dihantar."
                        confirm-label="Ya, hantar"
                        button-class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700"
                    >Hantar review</UiConfirmSubmit>
                </form>
            </section>
        </div>

        <aside class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 shadow-xl shadow-brand-900/5 lg:sticky lg:top-28 lg:self-start">
            <div>
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Jumlah pakej</p>
                <p class="font-display text-3xl font-semibold">{{ booking.total }}</p>
                <p class="text-sm text-ink-muted">Disahkan dibayar {{ booking.paid }}</p>
            </div>

            <ul v-if="payments.length" class="flex flex-col gap-3">
                <li v-for="payment in payments" :key="payment.reference" class="flex flex-col gap-2 rounded-xl border border-line p-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold">{{ payment.amount }}</span>
                        <span :class="['rounded-full px-2.5 py-0.5 text-[11px] font-medium', tones[payment.status_tone] || tones.muted]">{{ payment.status_label }}</span>
                    </div>

                    <p class="text-xs text-ink-muted">{{ payment.paid_on }} · {{ payment.reference }}</p>
                    <p v-if="payment.note" class="text-xs break-words text-ink-muted">{{ payment.note }}</p>

                    <a v-if="payment.receipt_url" :href="payment.receipt_url" target="_blank" rel="noopener" class="text-xs font-medium text-brand-700 underline underline-offset-4">Lihat resit</a>

                    <UiConfirm
                        v-if="payment.destroy_url"
                        :action="payment.destroy_url"
                        method="DELETE"
                        title="Buang rekod bayaran ini?"
                        message="Rekod ini belum disahkan vendor. Anda boleh merekodkannya semula selepas ini."
                        confirm-label="Ya, buang"
                        tone="danger"
                        :csrf="csrf"
                    >Buang rekod</UiConfirm>
                </li>
            </ul>

            <p v-else class="rounded-xl bg-surface-muted p-4 text-xs text-ink-muted">Belum ada bayaran direkod. Berbincang dengan vendor tentang jumlah dan cara bayaran, kemudian rekodkannya di sini.</p>

            <p class="text-xs text-ink-muted">Komisen platform {{ booking.commission_rate }}% dikira daripada nilai booking ini.</p>

            <UiConfirm
                v-if="cancelForm"
                :action="cancelForm.action"
                title="Batalkan booking ini?"
                message="Vendor akan dimaklumkan. Tindakan ini tidak boleh dibatalkan — anda perlu menempah semula jika berubah fikiran."
                confirm-label="Ya, batalkan booking"
                cancel-label="Jangan batalkan"
                tone="danger"
                trigger-class="w-full rounded-full border border-line px-4 py-2.5 text-sm font-medium text-ink-muted transition hover:border-red-300 hover:text-red-700"
                :csrf="csrf"
            >Batalkan booking</UiConfirm>
        </aside>
    </div>
</template>
