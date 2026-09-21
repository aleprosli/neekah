<script setup>
/** Reporting a vendor. Every report is read by an admin before anything happens. */
import { ref } from 'vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    types: { type: Array, required: true },
    bookings: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const form = ref({ type: '', booking_id: '', description: '' });
const messages = Object.values(props.errors);
</script>

<template>
    <ul v-if="messages.length" class="mt-6 flex flex-col gap-1 rounded-2xl bg-brand-50 p-4 text-sm text-brand-800">
        <li v-for="message in messages" :key="message">{{ message }}</li>
    </ul>

    <form :action="action" method="POST" class="mt-6 flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
        <input type="hidden" name="_token" :value="csrf">

        <UiSelect v-model="form.type" :label="$t('report.type')" name="type" :options="types" :placeholder="$t('report.type_placeholder')" :error="errors.type" required />

        <UiSelect
            v-if="bookings.length"
            v-model="form.booking_id"
            :label="$t('report.booking')"
            name="booking_id"
            :options="bookings"
            :placeholder="$t('report.booking_placeholder')"
            :error="errors.booking_id"
        />

        <UiTextarea
            v-model="form.description"
            :label="$t('report.what_happened')"
            name="description"
            :rows="6"
            :placeholder="$t('report.what_happened_placeholder')"
            :error="errors.description"
            required
        />

        <div class="rounded-xl bg-surface-muted p-4 text-xs text-ink-muted">
            Laporan palsu boleh menjejaskan vendor yang jujur. Hantar hanya jika anda benar-benar mengalami masalah ini.
        </div>

        <div class="flex gap-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('report.submit') }}</button>
            <a :href="cancelUrl" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('common.cancel') }}</a>
        </div>
    </form>
</template>
