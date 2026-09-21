<script setup>
/** One enquiry, the vendor's reply, and who is asking. */
import { ref } from 'vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    enquiry: { type: Object, required: true },
    action: { type: String, required: true },
    recordBookingUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const reply = ref(props.enquiry.reply ?? '');
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_300px]">
        <div class="flex min-w-0 flex-col gap-4">
            <div class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('vendor_enquiry.mesej') }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ enquiry.message }}</p>
            </div>

            <div v-if="enquiry.reply" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-xs font-semibold tracking-wide text-emerald-800 uppercase">Balasan anda · {{ enquiry.replied_at }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ enquiry.reply }}</p>
            </div>

            <form :action="action" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">

                <UiTextarea
                    v-model="reply"
                    :label="enquiry.reply ? $t('vendor_enquiry.kemas_kini_balasan') : $t('vendor_enquiry.balas')"
                    name="reply"
                    :rows="5"
                    :placeholder="$t('vendor_enquiry.terima_kasih_atas_enquiry_anda')"
                    :error="errors.reply"
                    required
                />

                <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('vendor_enquiry.hantar_balasan') }}</button>
            </form>
        </div>

        <aside class="flex min-w-0 flex-col gap-3 rounded-2xl border border-line p-5 text-sm lg:self-start">
            <div>
                <p class="text-ink-muted">{{ $t('vendor_enquiry.pelanggan') }}</p>
                <p class="font-medium">{{ enquiry.customer.name }}</p>
                <p class="break-words text-ink-muted">{{ enquiry.customer.contact }}</p>
            </div>

            <div v-if="enquiry.event_date">
                <p class="text-ink-muted">{{ $t('vendor_enquiry.tarikh_majlis') }}</p>
                <p class="font-medium">{{ enquiry.event_date }}</p>
            </div>

            <div v-if="enquiry.package">
                <p class="text-ink-muted">{{ $t('vendor_enquiry.pakej_diminati') }}</p>
                <p class="font-medium">{{ enquiry.package }}</p>
            </div>

            <div v-if="enquiry.wedding">
                <p class="text-ink-muted">{{ $t('vendor_enquiry.majlis') }}</p>
                <p class="font-medium">{{ enquiry.wedding.title }}</p>
                <p class="text-ink-muted">{{ enquiry.wedding.summary }}</p>
            </div>

            <a :href="recordBookingUrl" class="mt-2 rounded-full border border-line px-4 py-2 text-center font-medium transition hover:border-brand-400">{{ $t('vendor_enquiry.rekod_booking_untuk_pelanggan_ini') }}</a>
        </aside>
    </div>
</template>
