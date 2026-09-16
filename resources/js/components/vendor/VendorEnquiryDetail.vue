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
    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <div class="flex flex-col gap-4">
            <div class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Mesej</p>
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
                    :label="enquiry.reply ? 'Kemas kini balasan' : 'Balas'"
                    name="reply"
                    :rows="5"
                    placeholder="Terima kasih atas enquiry anda…"
                    :error="errors.reply"
                    required
                />

                <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Hantar balasan</button>
            </form>
        </div>

        <aside class="flex flex-col gap-3 rounded-2xl border border-line p-5 text-sm lg:self-start">
            <div>
                <p class="text-ink-muted">Pelanggan</p>
                <p class="font-medium">{{ enquiry.customer.name }}</p>
                <p class="break-words text-ink-muted">{{ enquiry.customer.contact }}</p>
            </div>

            <div v-if="enquiry.event_date">
                <p class="text-ink-muted">Tarikh majlis</p>
                <p class="font-medium">{{ enquiry.event_date }}</p>
            </div>

            <div v-if="enquiry.package">
                <p class="text-ink-muted">Pakej diminati</p>
                <p class="font-medium">{{ enquiry.package }}</p>
            </div>

            <div v-if="enquiry.wedding">
                <p class="text-ink-muted">Majlis</p>
                <p class="font-medium">{{ enquiry.wedding.title }}</p>
                <p class="text-ink-muted">{{ enquiry.wedding.summary }}</p>
            </div>

            <a :href="recordBookingUrl" class="mt-2 rounded-full border border-line px-4 py-2 text-center font-medium transition hover:border-brand-400">Rekod booking untuk pelanggan ini</a>
        </aside>
    </div>
</template>
